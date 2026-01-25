<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Timers;

use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;
use Fxify\DxtradeWebsocket\Services\DxtradeSessionManager;
use Swoole\Timer;
use Throwable;

class DxtradeSessionRenewalTimer
{
    use DxtradeWebsocketProvidesCommandOutput;

    private ?int $timerId = null;

    public function __construct(
        private DxtradeSessionManager $sessionManager,
    ) {}

    public function start(): void
    {
        if ($this->timerId !== null) {
            return;
        }

        // Check session every 5 minutes
        $intervalMs = 5 * 60 * 1000;

        $this->timerId = Timer::tick($intervalMs, function () {
            try {
                if ($this->sessionManager->needsRenewal()) {
                    $this->comment("Session expiring soon, renewing...");
                    $this->sessionManager->ping();
                    $this->info("Session renewed successfully");
                }
            } catch (Throwable $e) {
                $this->report($e);
                $this->error("Failed to renew session: {$e->getMessage()}");
            }
        });

        $this->info("Session renewal timer started");
    }

    public function stop(): void
    {
        if ($this->timerId !== null) {
            Timer::clear($this->timerId);
            $this->timerId = null;
            $this->info("Session renewal timer stopped");
        }
    }
}
