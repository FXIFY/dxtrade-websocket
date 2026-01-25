<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Contracts\Processors;

interface DxtradeWebsocketEventProcessorContract
{
    /**
     * Process a DXTrade websocket event
     *
     * @param  array<string, mixed>  $event
     */
    public function process(array $event): void;
}
