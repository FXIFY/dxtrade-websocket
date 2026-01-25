<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Coroutines;

use Fxify\DxtradeWebsocket\Clients\DxtradeWebsocketClient;
use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;
use Fxify\DxtradeWebsocket\Data\DxtradePushApiMessage;
use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketSubscription;

/**
 * DXTrade Websocket Subscription Coroutine
 *
 * Per Push API docs, subscription messages follow this format:
 * {
 *   "type": "AccountPortfoliosSubscriptionRequest",
 *   "requestId": "unique-id",
 *   "timestamp": 1234567890000,
 *   "session": "session-token",
 *   "payload": { ... subscription-specific payload ... }
 * }
 */
class DxtradeWebsocketSubscribeToSubscriptionCoroutine
{
    use DxtradeWebsocketProvidesCommandOutput;

    public function handle(
        DxtradeWebsocketClient $client,
        DxtradeWebsocketSubscription $subscription,
        string $sessionToken,
        array $payload = []
    ): void {
        $this->info("Subscribing to {$subscription->getLabel()}");

        // Build Push API subscription message
        $message = DxtradePushApiMessage::create(
            type: $subscription->getRequestType(),
            session: $sessionToken,
            payload: $payload,
        );

        $sent = $client->push($message->toJson());

        if (! $sent) {
            $this->error("Failed to subscribe to {$subscription->value}");

            return;
        }

        $this->info("Successfully sent subscription request for {$subscription->getLabel()}");
    }
}
