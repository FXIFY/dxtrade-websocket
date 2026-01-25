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

        /**
         * Configure which events to subscribe to
         */
        'subscriptions' => [
            'accounts' => env('DXTRADE_WEBSOCKET_SUBSCRIBE_ACCOUNTS_ENABLED', false),
            'orders' => env('DXTRADE_WEBSOCKET_SUBSCRIBE_ORDERS_ENABLED', false),
            'positions' => env('DXTRADE_WEBSOCKET_SUBSCRIBE_POSITIONS_ENABLED', false),
            'trades' => env('DXTRADE_WEBSOCKET_SUBSCRIBE_TRADES_ENABLED', false),
            'quotes' => env('DXTRADE_WEBSOCKET_SUBSCRIBE_QUOTES_ENABLED', false),
        ],
    ],

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
];
