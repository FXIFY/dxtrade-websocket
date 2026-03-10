<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Services;

use Fxify\DxtradeWebsocket\Data\DxtradePushSessionData;
use Fxify\DxtradeWebsocket\Data\DxtradeSessionData;
use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketChannel;
use Fxify\DxtradeWebsocket\Exceptions\DxtradeSessionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\Cache;

/**
 * DXTrade Session Manager
 *
 * Manages authentication and push session lifecycle for DXTrade API:
 * - Login to obtain session token
 * - Ping to keep session alive
 * - Logout to terminate session
 * - Create push session for websocket connections
 */
class DxtradeSessionManager
{
    private ?DxtradeSessionData $currentSession = null;

    private ?DxtradePushSessionData $currentPushSession = null;

    public function __construct(
        private readonly Http $http,
        private readonly string $baseUrl,
        private readonly ?string $authUrl,
        private readonly ?string $pushSessionUrl,
        private readonly ?string $websocketUrl,
        private readonly string $connectionMode,
        private readonly string $username,
        private readonly string $password,
        private readonly string $domain,
        private readonly int $sessionTtl = 3600, // 1 hour default
    ) {}

    /**
     * Get the current session, creating one if necessary
     */
    public function getSession(): DxtradeSessionData
    {
        if ($this->currentSession && ! $this->currentSession->isExpired()) {
            return $this->currentSession;
        }

        return $this->login();
    }

    /**
     * Login to DXTrade and obtain a session token
     *
     * Per DXTrade API docs: POST /login with username, domain, password
     * Returns sessionToken to be used in Authorization header as: DXAPI <token>
     */
    public function login(): DxtradeSessionData
    {
        $response = $this->http
            ->baseUrl($this->getAuthBaseUrl())
            ->post('/login', [
                'username' => $this->username,
                'domain' => $this->domain,
                'password' => $this->password,
            ]);

        if (! $response->successful()) {
            throw DxtradeSessionException::loginFailed(
                $response->json('message') ?? $response->body()
            );
        }

        $data = $response->json();

        $this->currentSession = new DxtradeSessionData(
            sessionToken: $data['sessionToken'] ?? '',
            expiresAt: time() + $this->sessionTtl,
            userId: $data['userId'] ?? null,
        );

        // Cache the session
        Cache::put(
            $this->getSessionCacheKey(),
            $this->currentSession->toArray(),
            $this->sessionTtl
        );

        return $this->currentSession;
    }

    /**
     * Ping to keep the session alive
     *
     * Per DXTrade API docs: POST /ping with Authorization: DXAPI <token> header
     * Resets the session expiration timeout for token authentication
     */
    public function ping(): bool
    {
        $session = $this->getSession();

        $response = $this->http
            ->baseUrl($this->getAuthBaseUrl())
            ->withHeaders([
                'Authorization' => "DXAPI {$session->sessionToken}",
            ])
            ->post('/ping');

        if (! $response->successful()) {
            throw DxtradeSessionException::pingFailed(
                $response->json('message') ?? $response->body()
            );
        }

        // Extend session expiry
        $this->currentSession = new DxtradeSessionData(
            sessionToken: $session->sessionToken,
            expiresAt: time() + $this->sessionTtl,
            userId: $session->userId,
        );

        Cache::put(
            $this->getSessionCacheKey(),
            $this->currentSession->toArray(),
            $this->sessionTtl
        );

        return true;
    }

    /**
     * Logout and terminate the session
     *
     * Per DXTrade API docs: POST /logout with Authorization: DXAPI <token> header
     * Explicitly expires the authorization token for token authentication
     */
    public function logout(): bool
    {
        if (! $this->currentSession) {
            return true;
        }

        $response = $this->http
            ->baseUrl($this->getAuthBaseUrl())
            ->withHeaders([
                'Authorization' => "DXAPI {$this->currentSession->sessionToken}",
            ])
            ->post('/logout');

        $this->currentSession = null;
        Cache::forget($this->getSessionCacheKey());

        return $response->successful();
    }

    /**
     * Create a push session for websocket connections
     *
     * Uses Authorization: DXAPI <token> header for authenticated requests
     */
    public function createPushSession(DxtradeWebsocketChannel $channel): DxtradePushSessionData
    {
        $session = $this->getSession();

        if ($this->usesDirectWebsocketConnection()) {
            return $this->buildDirectWebsocketSession();
        }

        $response = $this->http
            ->baseUrl($this->getPushSessionBaseUrl())
            ->withHeaders([
                'Authorization' => "DXAPI {$session->sessionToken}",
            ])
            ->post('/push/session', [
                'channel' => $channel->value,
            ]);

        if (! $response->successful()) {
            throw DxtradeSessionException::pushSessionCreationFailed(
                $response->json('message') ?? $response->body()
            );
        }

        $data = $response->json();

        $this->currentPushSession = new DxtradePushSessionData(
            pushSessionId: $data['pushSessionId'] ?? '',
            websocketUrl: $data['websocketUrl'] ?? '',
            expiresAt: time() + ($data['ttl'] ?? 3600),
        );

        return $this->currentPushSession;
    }

    /**
     * Get the current push session
     */
    public function getPushSession(): ?DxtradePushSessionData
    {
        return $this->currentPushSession;
    }

    /**
     * Check if session needs renewal
     */
    public function needsRenewal(): bool
    {
        if (! $this->currentSession) {
            return true;
        }

        return $this->currentSession->isExpiringSoon();
    }

    private function getSessionCacheKey(): string
    {
        return "dxtrade:session:{$this->username}";
    }

    public function usesDirectWebsocketConnection(): bool
    {
        return $this->connectionMode === 'direct';
    }

    private function getAuthBaseUrl(): string
    {
        return $this->authUrl ?: $this->baseUrl;
    }

    private function getPushSessionBaseUrl(): string
    {
        return $this->pushSessionUrl ?: $this->baseUrl;
    }

    private function getDirectWebsocketUrl(): string
    {
        $url = $this->websocketUrl ?: $this->getAuthBaseUrl();

        return match (true) {
            str_starts_with($url, 'https://') => 'wss://' . substr($url, 8),
            str_starts_with($url, 'http://') => 'ws://' . substr($url, 7),
            default => $url,
        };
    }

    private function buildDirectWebsocketSession(): DxtradePushSessionData
    {
        $this->currentPushSession = new DxtradePushSessionData(
            pushSessionId: '',
            websocketUrl: $this->getDirectWebsocketUrl(),
            expiresAt: time() + $this->sessionTtl,
        );

        return $this->currentPushSession;
    }
}
