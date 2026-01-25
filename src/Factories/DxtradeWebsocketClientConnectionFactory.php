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

        // Use path from URL if provided, otherwise build from channel with query params
        $path = $parsedUrl['path'] ?? $channel->getPath($format, $compression);

        // Add query parameters if they're in the original URL
        if (isset($parsedUrl['query'])) {
            $path .= '?' . $parsedUrl['query'];
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
        $client->setHeaders([
            'User-Agent' => 'DXTrade-Websocket-Client/1.0',
            'X-Push-Session-Id' => $pushSession->pushSessionId,
        ]);

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
