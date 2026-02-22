<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Data;

use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketEventType;

readonly class DxtradeLifecycleEventData
{
    /**
     * @param array<string, mixed> $responsePayload
     * @param array<string, mixed>|null $request
     */
    public function __construct(
        public DxtradeWebsocketEventType $type,
        public string $lifecycle,
        public ?string $requestId,
        public ?string $responseType,
        public array $responsePayload,
        public ?array $request = null,
    ) {}

    public function isSubscriptionConfirmed(): bool
    {
        return $this->type === DxtradeWebsocketEventType::SubscriptionConfirmed
            && $this->lifecycle === 'subscription_confirmed';
    }

    public function isSubscriptionClosed(): bool
    {
        return $this->type === DxtradeWebsocketEventType::SubscriptionClosed
            && $this->lifecycle === 'subscription_closed';
    }

    public function isRequestRejected(): bool
    {
        return $this->type === DxtradeWebsocketEventType::Error
            && $this->lifecycle === 'request_rejected';
    }

    public function isProtocolError(): bool
    {
        return $this->type === DxtradeWebsocketEventType::Error
            && $this->lifecycle === 'error';
    }
}
