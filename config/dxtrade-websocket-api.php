<?php

declare(strict_types=1);

return [
    /**
     * DXTrade WebSocket API Configuration
     *
     * Configure the websocket connection settings for different DXTrade environments
     */
    'default' => [
        'url' => (string) env('DXTRADE_WEBSOCKET_URL'),

        'username' => (string) env('DXTRADE_WEBSOCKET_USERNAME'),

        'password' => (string) env('DXTRADE_WEBSOCKET_PASSWORD'),

        'domain' => (string) env('DXTRADE_WEBSOCKET_DOMAIN', 'default'),

        /**
         * Configure which subscriptions to enable
         * Maps to DxtradeWebsocketSubscription enum values
         */
        'subscriptions' => [
            'accounts' => env('DXTRADE_WEBSOCKET_SUBSCRIBE_ACCOUNTS_ENABLED', false), // AccountPortfolios
            'metrics' => env('DXTRADE_WEBSOCKET_SUBSCRIBE_METRICS_ENABLED', false), // AccountMetrics
            'events' => env('DXTRADE_WEBSOCKET_SUBSCRIBE_EVENTS_ENABLED', false), // AccountEvents
            'cash_transfers' => env('DXTRADE_WEBSOCKET_SUBSCRIBE_CASH_TRANSFERS_ENABLED', false), // CashTransfers
            'instruments' => env('DXTRADE_WEBSOCKET_SUBSCRIBE_INSTRUMENTS_ENABLED', false), // InstrumentInfo
        ],
    ],

    /**
     * Session TTL in seconds (default: 1 hour)
     */
    'session_ttl' => (int) env('DXTRADE_WEBSOCKET_SESSION_TTL_SECONDS', 3600),

    /**
     * Reconnection settings
     */
    'reconnect_delay' => (float) env('DXTRADE_WEBSOCKET_RECONNECT_DELAY_SECONDS', 1.0),

    'max_reconnect_attempts' => (int) env('DXTRADE_WEBSOCKET_MAX_RECONNECT_ATTEMPTS', 10),

    /**
     * Message timeout settings
     */
    'message_timeouts' => [
        'default' => (float) env('DXTRADE_WEBSOCKET_MESSAGE_TIMEOUT_SECONDS', 60.0),
    ],

    /**
     * Heartbeat/ping settings
     */
    'heartbeat' => [
        'enabled' => env('DXTRADE_WEBSOCKET_HEARTBEAT_ENABLED', true),
        'interval' => (int) env('DXTRADE_WEBSOCKET_HEARTBEAT_INTERVAL_SECONDS', 30),
    ],

    /**
     * Push API connection settings
     */
    'format' => env('DXTRADE_WEBSOCKET_FORMAT', 'json'), // json or xml
    'compression' => env('DXTRADE_WEBSOCKET_COMPRESSION', null), // null or 'gzip'
];
