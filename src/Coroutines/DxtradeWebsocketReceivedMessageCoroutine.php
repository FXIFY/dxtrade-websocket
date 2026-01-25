<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Coroutines;

use Fxify\DxtradeWebsocket\Clients\DxtradeWebsocketClient;
use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;
use Fxify\DxtradeWebsocket\Data\DxtradePushApiMessage;
use Fxify\DxtradeWebsocket\Data\DxtradePushApiResponse;
use Fxify\DxtradeWebsocket\Data\DxtradeWebsocketEventData;
use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketEventType;
use Swoole\WebSocket\Frame;
use Throwable;

class DxtradeWebsocketReceivedMessageCoroutine
{
    use DxtradeWebsocketProvidesCommandOutput;

    public function __construct(
        private DxtradeWebsocketEventJobCoroutine $eventJobCoroutine,
    ) {}

    public function handle(Frame $frame, DxtradeWebsocketClient $client): void
    {
        try {
            $data = json_decode($frame->data, true);

            if (! is_array($data)) {
                $this->comment("Invalid JSON received from websocket");

                return;
            }

            $this->comment("Received message: " . json_encode($data));

            // Parse as Push API response
            $response = DxtradePushApiResponse::fromArray($data);

            // Handle PingRequest from server
            if ($response->isPingRequest()) {
                $this->handlePingRequest($response, $client);

                return;
            }

            // Handle subscription responses
            if ($response->isSubscriptionResponse()) {
                $this->comment("Subscription response received: {$response->type}");

                return;
            }

            // Handle errors
            if ($response->isError()) {
                $this->error("Error response: {$response->type} - " . json_encode($response->payload));

                return;
            }

            // Parse the event type from the Push API message type
            $eventType = $this->parseEventType($response);

            if (! $eventType->isProcessable()) {
                $this->comment("Event type {$eventType->value} is not processable, skipping");

                return;
            }

            // Create event data from Push API response
            $eventData = new DxtradeWebsocketEventData(
                type: $eventType,
                payload: $response->payload,
                accountId: $response->payload['accountId'] ?? null,
                timestamp: $response->timestamp ?? (int) (microtime(true) * 1000),
            );

            // Dispatch to event job coroutine
            $this->eventJobCoroutine->handle($eventData, $client);

        } catch (Throwable $e) {
            $this->report($e);
            $this->error("Error processing websocket message: {$e->getMessage()}");
        }
    }

    /**
     * Handle server-initiated PingRequest
     * Per Push API: Respond with Ping message to extend session
     */
    private function handlePingRequest(DxtradePushApiResponse $response, DxtradeWebsocketClient $client): void
    {
        $this->comment("Received PingRequest, responding with Ping");

        $sessionToken = $client->getSessionToken();

        if (! $sessionToken) {
            $this->error("No session token available to respond to PingRequest");

            return;
        }

        // Create Ping response message per Push API
        $pingMessage = DxtradePushApiMessage::create(
            type: 'Ping',
            session: $sessionToken,
            payload: [],
        );

        $sent = $client->push($pingMessage->toJson());

        if (! $sent) {
            $this->error("Failed to send Ping response");

            return;
        }

        $this->comment("Ping response sent successfully");
    }

    /**
     * Parse event type from Push API response
     * Maps Push API message types to internal event types
     */
    private function parseEventType(DxtradePushApiResponse $response): DxtradeWebsocketEventType
    {
        // Per Push API, message type indicates the event type
        // Examples: AccountPortfolioUpdate, AccountMetricUpdate, etc.
        $type = $response->type;

        // Try direct mapping from message type
        if ($enum = DxtradeWebsocketEventType::tryFrom($type)) {
            return $enum;
        }

        // Map common Push API message types to event types
        return match (true) {
            str_contains($type, 'AccountPortfolio') => DxtradeWebsocketEventType::AccountPortfolio,
            str_contains($type, 'AccountMetric') => DxtradeWebsocketEventType::AccountMetric,
            str_contains($type, 'AccountEvent') => DxtradeWebsocketEventType::AccountEvent,
            str_contains($type, 'CashTransfer') => DxtradeWebsocketEventType::CashTransfer,
            str_contains($type, 'Instrument') => DxtradeWebsocketEventType::InstrumentInfo,
            default => DxtradeWebsocketEventType::Error,
        };
    }
}
