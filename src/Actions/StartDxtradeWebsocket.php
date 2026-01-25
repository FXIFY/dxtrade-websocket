<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Actions;

use Fxify\DxtradeWebsocket\Clients\DxtradeWebsocketClient;
use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketPrintsClientStatus;
use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;
use Fxify\DxtradeWebsocket\Coroutines\DxtradeWebsocketSubscribeToEnabledSubscriptionsCoroutine;
use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketChannel;
use Fxify\DxtradeWebsocket\Factories\DxtradeWebsocketClientConnectionFactory;
use Fxify\DxtradeWebsocket\Listeners\DxtradeWebsocketMessageListener;
use Fxify\DxtradeWebsocket\Managers\DxtradeWebsocketCoroutineManager;
use Fxify\DxtradeWebsocket\Timers\DxtradeSessionRenewalTimer;
use Fxify\DxtradeWebsocket\Timers\DxtradeWebsocketHeartbeatTimer;
use Swoole\Coroutine;
use Swoole\Timer;
use Throwable;

class StartDxtradeWebsocket
{
    use DxtradeWebsocketPrintsClientStatus;
    use DxtradeWebsocketProvidesCommandOutput;

    public function __construct(
        private DxtradeWebsocketCoroutineManager $coroutineManager,
        private DxtradeWebsocketClientConnectionFactory $clientFactory,
        private DxtradeWebsocketMessageListener $messageListener,
        private DxtradeWebsocketSubscribeToEnabledSubscriptionsCoroutine $subscribeToEnabledSubscriptionsCoroutine,
        private DxtradeWebsocketHeartbeatTimer $heartbeatTimer,
        private DxtradeSessionRenewalTimer $sessionRenewalTimer,
    ) {}

    public function __invoke(DxtradeWebsocketChannel $channel): void
    {
        $reconnectDelaySeconds = config()->float('dxtrade-websocket-api.reconnect_delay', 1.0);
        $maxReconnectAttempts = config()->int('dxtrade-websocket-api.max_reconnect_attempts', 10);

        Coroutine\run(function () use ($channel, $reconnectDelaySeconds, $maxReconnectAttempts) {
            /** @var DxtradeWebsocketClient|null $client */
            $client = null;
            $attemptCount = 0;

            // Start session renewal timer
            $this->sessionRenewalTimer->start();

            try {
                /** @phpstan-ignore-next-line */
                while (true) {
                    if ($client?->isConnected()) {
                        $this->heartbeatTimer->start($client);
                        $this->subscribeToEnabledSubscriptionsCoroutine->start($client);
                        $this->messageListener->handle($client);

                        continue;
                    }

                    $client?->close();

                    // Check max reconnect attempts
                    if ($attemptCount >= $maxReconnectAttempts) {
                        $this->error("Maximum reconnection attempts ({$maxReconnectAttempts}) reached. Exiting.");

                        break;
                    }

                    if ($attemptCount > 0) {
                        $this->info("Reconnecting in {$reconnectDelaySeconds} seconds... (Attempt {$attemptCount}/{$maxReconnectAttempts})");
                        Coroutine::sleep($reconnectDelaySeconds);
                    }

                    Timer::clearAll();
                    $this->coroutineManager->clear();

                    $this->info('Starting DXTrade websocket client.');
                    $client = $this->clientFactory->make($channel);
                    $attemptCount++;
                }
            } catch (Throwable $e) {
                $this->report($e);
                $this->error($e->getMessage());
            } finally {
                $this->heartbeatTimer->stop();
                $this->sessionRenewalTimer->stop();
            }
        });
    }
}
