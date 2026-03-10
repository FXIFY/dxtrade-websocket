<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Factories;

use Fxify\DxtradeWebsocket\Clients\DxtradeWebsocketClient;
use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketPrintsClientStatus;
use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;
use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketChannel;
use Fxify\DxtradeWebsocket\Services\DxtradeSessionManager;
use RuntimeException;

class DxtradeWebsocketClientConnectionFactory
{
    use DxtradeWebsocketPrintsClientStatus;
    use DxtradeWebsocketProvidesCommandOutput;

    public function __construct(
        private DxtradeSessionManager $sessionManager,
    ) {}

    public function make(DxtradeWebsocketChannel $channel): DxtradeWebsocketClient
    {
        // Get current session for token
        $session = $this->sessionManager->getSession();

        // Create push session for websocket
        $pushSession = $this->sessionManager->createPushSession($channel);

        // Parse websocket URL
        $parsedUrl = parse_url($pushSession->websocketUrl);
        $host = $parsedUrl['host'] ?? '';
        $port = $parsedUrl['port'] ?? ($parsedUrl['scheme'] === 'wss' ? 443 : 80);
        $ssl = ($parsedUrl['scheme'] ?? 'ws') === 'wss';

        // Get format and compression from config for Push API query parameters
        $format = config('dxtrade-websocket-api.format', 'json');
        $compression = config('dxtrade-websocket-api.compression');

        // Direct websocket URLs point to the Push API root. Append the channel-specific path.
        if (blank($pushSession->pushSessionId)) {
            $basePath = rtrim($parsedUrl['path'] ?? '', '/');
            $channelPath = $channel->getBasePath();

            $path = match ($channelPath) {
                '/' => ($basePath === '' ? '' : $basePath) . '/',
                default => str_ends_with($basePath, $channelPath)
                    ? $basePath
                    : $basePath . $channelPath,
            };
        } else {
            $path = $parsedUrl['path'] ?? $channel->getBasePath();
        }

        $query = [];

        parse_str($parsedUrl['query'] ?? '', $query);

        if ($format && ! array_key_exists('format', $query)) {
            $query['format'] = $format;
        }

        if ($compression && ! array_key_exists('compression', $query)) {
            $query['compression'] = $compression;
        }

        if (! empty($query)) {
            $path .= '?' . http_build_query($query);
        }

        $this->info("Connecting to DXTrade websocket: {$pushSession->websocketUrl}");

        $client = new DxtradeWebsocketClient(
            channel: $channel,
            host: $host,
            port: $port,
            ssl: $ssl,
        );

        // Store session token in client for Push API messages
        $client->setSessionToken($session->sessionToken);

        // Set websocket headers
        $headers = [
            'User-Agent' => 'DXTrade-Websocket-Client/1.0',
        ];

        if (filled($pushSession->pushSessionId)) {
            $headers['X-Push-Session-Id'] = $pushSession->pushSessionId;
        } else {
            $headers['Authorization'] = "DXAPI {$session->sessionToken}";
        }

        $client->setHeaders($headers);

        // Upgrade to websocket
        $upgraded = $client->upgrade($path);

        if (! $upgraded) {
            $this->error("Failed to connect to DXTrade websocket");
            $this->printClientStatus($client);

            throw new RuntimeException("Websocket connection failed: {$client->errMsg}");
        }

        $this->info("Successfully connected to DXTrade {$channel->getLabel()}");

        return $client;
    }
}
