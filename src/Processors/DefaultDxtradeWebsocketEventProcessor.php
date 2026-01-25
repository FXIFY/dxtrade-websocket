<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Processors;

use Fxify\DxtradeWebsocket\Contracts\Processors\DxtradeWebsocketEventProcessorContract;
use Illuminate\Support\Facades\Log;

class DefaultDxtradeWebsocketEventProcessor implements DxtradeWebsocketEventProcessorContract
{
    public function process(array $event): void
    {
        Log::info('DXTrade websocket event received', [
            'event' => $event,
        ]);
    }
}
