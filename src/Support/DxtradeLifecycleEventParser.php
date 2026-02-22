<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Support;

use Fxify\DxtradeWebsocket\Data\DxtradeLifecycleEventData;
use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketEventType;

class DxtradeLifecycleEventParser
{
    /**
     * Parse a lifecycle/control event from a raw websocket event payload.
     *
     * @param array<string, mixed> $event
     */
    public static function parse(array $event): ?DxtradeLifecycleEventData
    {
        $type = DxtradeWebsocketEventType::tryFrom((string) ($event['type'] ?? ''));

        if (! $type || ! in_array($type, [
            DxtradeWebsocketEventType::SubscriptionConfirmed,
            DxtradeWebsocketEventType::SubscriptionClosed,
            DxtradeWebsocketEventType::Error,
        ], true)) {
            return null;
        }

        $payload = $event['payload'] ?? null;
        if (! is_array($payload)) {
            return null;
        }

        $lifecycle = $payload['lifecycle'] ?? null;
        if (! is_string($lifecycle) || $lifecycle === '') {
            return null;
        }

        $responsePayload = $payload['responsePayload'] ?? [];
        if (! is_array($responsePayload)) {
            $responsePayload = [];
        }

        $request = $payload['request'] ?? null;
        if (! is_array($request)) {
            $request = null;
        }

        return new DxtradeLifecycleEventData(
            type: $type,
            lifecycle: $lifecycle,
            requestId: is_string($payload['requestId'] ?? null) ? $payload['requestId'] : null,
            responseType: is_string($payload['responseType'] ?? null) ? $payload['responseType'] : null,
            responsePayload: $responsePayload,
            request: $request,
        );
    }
}
