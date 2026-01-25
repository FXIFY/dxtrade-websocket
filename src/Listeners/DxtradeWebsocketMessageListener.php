<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Listeners;

use Fxify\DxtradeWebsocket\Clients\DxtradeWebsocketClient;
use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;
use Fxify\DxtradeWebsocket\Coroutines\DxtradeWebsocketReceivedMessageCoroutine;
use Swoole\Coroutine;
use Swoole\WebSocket\Frame;

class DxtradeWebsocketMessageListener
{
    use DxtradeWebsocketProvidesCommandOutput;

    public function __construct(
        private DxtradeWebsocketReceivedMessageCoroutine $receivedMessageCoroutine,
    ) {}

    public function handle(DxtradeWebsocketClient $client): void
    {
        $frame = $client->recv();

        if ($frame === false || $frame === '') {
            $this->comment("Connection closed or empty frame received");
            $client->close();

            return;
        }

        if ($frame instanceof Frame) {
            Coroutine::create(function () use ($frame, $client) {
                $this->receivedMessageCoroutine->handle($frame, $client);
            });
        }
    }
}
