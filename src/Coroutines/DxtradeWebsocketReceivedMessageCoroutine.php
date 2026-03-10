<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Coroutines;

use Fxify\DxtradeWebsocket\Clients\DxtradeWebsocketClient;
use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;
use Fxify\DxtradeWebsocket\Data\DxtradePushApiMessage;
use Fxify\DxtradeWebsocket\Data\DxtradePushApiResponse;
use Fxify\DxtradeWebsocket\Data\DxtradePushRequestContext;
use Fxify\DxtradeWebsocket\Data\DxtradeWebsocketEventData;
use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketEventType;
use Fxify\DxtradeWebsocket\Services\DxtradePushRequestCorrelationManager;
use Swoole\WebSocket\Frame;
use Throwable;

class DxtradeWebsocketReceivedMessageCoroutine
{
    use DxtradeWebsocketProvidesCommandOutput;

    public function __construct(
        private DxtradeWebsocketEventJobCoroutine $eventJobCoroutine,
        private DxtradePushRequestCorrelationManager $requestCorrelationManager,
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

            // Handle Ping acks
            if ($response->isPing()) {
                $this->comment("Ping response received");

                return;
            }

            // Handle subscription responses
            if ($response->isSubscriptionResponse()) {
                $this->comment("Subscription response received: {$response->type}");
                $context = $response->requestId
                    ? $this->requestCorrelationManager->confirmSubscription($response->requestId)
                    : null;
                $this->dispatchLifecycleEvent(
                    type: DxtradeWebsocketEventType::SubscriptionConfirmed,
                    response: $response,
                    lifecycle: 'subscription_confirmed',
                    client: $client,
                    context: $context,
                );

                return;
            }

            // Handle closed subscriptions explicitly (do not treat as data event)
            if ($response->isSubscriptionClosed()) {
                $this->error("Subscription closed: {$response->type} - " . json_encode($response->payload));
                $context = $response->requestId
                    ? $this->requestCorrelationManager->closeSubscription($response->requestId)
                    : null;
                $this->dispatchLifecycleEvent(
                    type: DxtradeWebsocketEventType::SubscriptionClosed,
                    response: $response,
                    lifecycle: 'subscription_closed',
                    client: $client,
                    context: $context,
                );

                return;
            }

            // Handle request-level rejections explicitly
            if ($response->isRequestError() || $response->isReject()) {
                $this->error("Request rejected: {$response->type} - " . json_encode($response->payload));
                $context = $response->requestId
                    ? $this->requestCorrelationManager->failRequest($response->requestId)
                    : null;
                $this->dispatchLifecycleEvent(
                    type: DxtradeWebsocketEventType::Error,
                    response: $response,
                    lifecycle: 'request_rejected',
                    client: $client,
                    context: $context,
                );

                return;
            }

            // Handle errors
            if ($response->isError()) {
                $this->error("Error response: {$response->type} - " . json_encode($response->payload));
                $context = $response->requestId
                    ? $this->requestCorrelationManager->failRequest($response->requestId)
                    : null;
                $this->dispatchLifecycleEvent(
                    type: DxtradeWebsocketEventType::Error,
                    response: $response,
                    lifecycle: 'error',
                    client: $client,
                    context: $context,
                );

                return;
            }

            // Parse the event type from the Push API message type
            $eventType = $this->parseEventType($response);

            if (! $eventType->isProcessable()) {
                $this->comment("Event type {$eventType->value} is not processable, skipping");

                return;
            }

            $timestamp = $this->normalizeTimestamp($response->timestamp);

            foreach ($this->extractEventPayloads($response, $eventType) as $payload) {
                $eventData = new DxtradeWebsocketEventData(
                    type: $eventType,
                    payload: $payload,
                    accountId: $payload['accountId'] ?? $payload['accountCode'] ?? null,
                    timestamp: $timestamp,
                );

                $this->eventJobCoroutine->handle($eventData, $client);
            }

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

        // Create Ping response message per Push API.
        // If requestId is present, echo it for request correlation.
        $pingMessage = $response->requestId
            ? new DxtradePushApiMessage(
                type: 'Ping',
                requestId: $response->requestId,
                timestamp: $response->timestamp ?? (int) (microtime(true) * 1000),
                session: $sessionToken,
                payload: [],
            )
            : DxtradePushApiMessage::create(
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

    private function dispatchLifecycleEvent(
        DxtradeWebsocketEventType $type,
        DxtradePushApiResponse $response,
        string $lifecycle,
        DxtradeWebsocketClient $client,
        ?DxtradePushRequestContext $context = null
    ): void {
        $payload = [
            'lifecycle' => $lifecycle,
            'responseType' => $response->type,
            'requestId' => $response->requestId,
            'responsePayload' => $response->payload,
        ];

        if ($context) {
            $payload['request'] = $context->toArray();
        }

        $eventData = new DxtradeWebsocketEventData(
            type: $type,
            payload: $payload,
            timestamp: $this->normalizeTimestamp($response->timestamp),
        );

        $this->eventJobCoroutine->handle($eventData, $client);
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractEventPayloads(
        DxtradePushApiResponse $response,
        DxtradeWebsocketEventType $eventType
    ): array {
        return match ($eventType) {
            DxtradeWebsocketEventType::AccountPortfolio => $this->extractCollectionPayloadItems($response->payload, 'portfolios'),
            DxtradeWebsocketEventType::AccountMetric => $this->extractCollectionPayloadItems($response->payload, 'metrics'),
            DxtradeWebsocketEventType::AccountEvent => $this->extractCollectionPayloadItems($response->payload, 'accountEvents'),
            DxtradeWebsocketEventType::CashTransfer => $this->extractCollectionPayloadItems($response->payload, 'cashTransfers'),
            DxtradeWebsocketEventType::InstrumentInfo => $this->extractCollectionPayloadItems($response->payload, 'instruments'),
            default => [$this->normalizeEventPayload($response->payload)],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function extractCollectionPayloadItems(array $payload, string $collectionKey): array
    {
        $items = $payload[$collectionKey] ?? null;

        if (! is_array($items)) {
            return [$this->normalizeEventPayload($payload)];
        }

        $normalizedItems = [];

        foreach ($items as $item) {
            if (is_array($item)) {
                $normalizedItems[] = $this->normalizeEventPayload($item);
            }
        }

        return $normalizedItems;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function normalizeEventPayload(array $payload): array
    {
        $account = $payload['account'] ?? null;

        if (
            ! isset($payload['accountCode'])
            && is_string($account)
            && $account !== ''
        ) {
            $parts = explode(':', $account, 2);

            $payload['clearingCode'] = $payload['clearingCode'] ?? ($parts[0] ?? null);
            $payload['accountCode'] = $parts[1] ?? $parts[0] ?? null;
        }

        $ownerLogin = $payload['owner']['login'] ?? null;

        if (! isset($payload['accountCode']) && is_string($ownerLogin) && $ownerLogin !== '') {
            $payload['accountCode'] = $ownerLogin;
        }

        if (! isset($payload['accountId']) && is_string($payload['accountCode'] ?? null)) {
            $payload['accountId'] = $payload['accountCode'];
        }

        return $payload;
    }

    private function normalizeTimestamp(int|string|null $timestamp): int
    {
        if (is_int($timestamp)) {
            return $timestamp;
        }

        if (is_numeric($timestamp)) {
            return (int) $timestamp;
        }

        if (is_string($timestamp) && filled($timestamp)) {
            $parsed = strtotime($timestamp);

            if ($parsed !== false) {
                return $parsed * 1000;
            }
        }

        return (int) (microtime(true) * 1000);
    }
}
