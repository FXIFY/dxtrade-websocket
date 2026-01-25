<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Timers;

use Fxify\DxtradeWebsocket\Clients\DxtradeWebsocketClient;
use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;
use Swoole\Timer;

class DxtradeWebsocketHeartbeatTimer
{
    use DxtradeWebsocketProvidesCommandOutput;

    private ?int $timerId = null;

    public function start(DxtradeWebsocketClient $client): void
    {
        if ($this->timerId !== null) {
            return;
        }

        $enabled = config('dxtrade-websocket-api.heartbeat.enabled', true);
        if (! $enabled) {
            return;
        }

        $intervalMs = config('dxtrade-websocket-api.heartbeat.interval', 30) * 1000;

        $this->timerId = Timer::tick($intervalMs, function () use ($client) {
            if (! $client->isConnected()) {
                $this->comment("Client not connected, skipping heartbeat");

                return;
            }

            $message = json_encode([
                'action' => 'ping',
                'timestamp' => time(),
            ]);

            $sent = $client->push($message);

            if ($sent) {
                $this->comment("Heartbeat sent");
            } else {
                $this->error("Failed to send heartbeat");
            }
        });

        $this->info("Heartbeat timer started (interval: {$intervalMs}ms)");
    }

    public function stop(): void
    {
        if ($this->timerId !== null) {
            Timer::clear($this->timerId);
            $this->timerId = null;
            $this->info("Heartbeat timer stopped");
        }
    }
}
