<?php

declare(strict_types=1);

return [
    /**
     * DXTrade Websocket Event Processor must be configured on the application
     * that is processing the websocket event jobs.
     *
     * The processor class must implement:
     * \Fxify\DxtradeWebsocket\Contracts\Processors\DxtradeWebsocketEventProcessorContract
     */
    'processor' => null,

    /**
     * Queue configuration for processing websocket events
     */
    'queue' => [
        'connection' => env('DXTRADE_WEBSOCKET_QUEUE_CONNECTION', 'redis'),
        'name' => env('DXTRADE_WEBSOCKET_QUEUE_NAME', 'dxtrade-websocket'),
    ],
];
