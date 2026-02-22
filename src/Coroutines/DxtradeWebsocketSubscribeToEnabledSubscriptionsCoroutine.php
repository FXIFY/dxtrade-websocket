<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Coroutines;

use Fxify\DxtradeWebsocket\Clients\DxtradeWebsocketClient;
use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;
use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketSubscription;
use Fxify\DxtradeWebsocket\Managers\DxtradeWebsocketCoroutineManager;
use Swoole\Coroutine;

class DxtradeWebsocketSubscribeToEnabledSubscriptionsCoroutine
{
    use DxtradeWebsocketProvidesCommandOutput;

    public function __construct(
        private DxtradeWebsocketCoroutineManager $coroutineManager,
        private DxtradeWebsocketSubscribeToSubscriptionCoroutine $subscribeToSubscriptionCoroutine,
    ) {}

    public function start(DxtradeWebsocketClient $client): void
    {
        $channel = $client->getChannel();
        $sessionToken = $client->getSessionToken();

        if (! $sessionToken) {
            $this->error('No session token available for subscriptions');

            return;
        }

        // Get enabled subscriptions from config
        $subscriptions = config('dxtrade-websocket-api.default.subscriptions', []);

        foreach (DxtradeWebsocketSubscription::cases() as $subscription) {
            // Only subscribe if it's for the current channel and enabled in config
            if ($subscription->getChannel() !== $channel) {
                continue;
            }

            $configKey = $this->getSubscriptionConfigKey($subscription);
            if (! ($subscriptions[$configKey] ?? false)) {
                continue;
            }

            $coroutineId = Coroutine::create(function () use ($client, $subscription, $sessionToken) {
                $payload = $this->getSubscriptionPayload($subscription);

                $this->subscribeToSubscriptionCoroutine->handle($client, $subscription, $sessionToken, $payload);
            });

            $this->coroutineManager->register(
                "subscribe_{$subscription->value}",
                $coroutineId
            );
        }
    }

    private function getSubscriptionConfigKey(DxtradeWebsocketSubscription $subscription): string
    {
        return match ($subscription) {
            DxtradeWebsocketSubscription::AccountPortfolios => 'accounts',
            DxtradeWebsocketSubscription::AccountMetrics => 'metrics',
            DxtradeWebsocketSubscription::AccountEvents => 'events',
            DxtradeWebsocketSubscription::CashTransfers => 'cash_transfers',
            DxtradeWebsocketSubscription::InstrumentInfo => 'instruments',
        };
    }

    /** @return array<string, mixed> */
    private function getSubscriptionPayload(DxtradeWebsocketSubscription $subscription): array
    {
        $configKey = $this->getSubscriptionConfigKey($subscription);
        $payload = config("dxtrade-websocket-api.default.subscription_payloads.{$configKey}", []);

        if (! is_array($payload)) {
            $this->error("Invalid payload config for {$subscription->value}; expected array");

            return [];
        }

        return $payload;
    }
}
