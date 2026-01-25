<?php

declare(strict_types=1);

use Fxify\DxtradeWebsocket\Processors\DefaultDxtradeWebsocketEventProcessor;
use Illuminate\Support\Facades\Log;

it('processes an event', function () {
    Log::shouldReceive('info')
        ->once()
        ->with('DXTrade websocket event received', [
            'event' => [
                'type' => 'test',
                'data' => 'test data',
            ],
        ]);

    $processor = new DefaultDxtradeWebsocketEventProcessor();

    $processor->process([
        'type' => 'test',
        'data' => 'test data',
    ]);
});
