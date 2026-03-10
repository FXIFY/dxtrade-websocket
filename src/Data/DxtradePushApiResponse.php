<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Data;

/**
 * DXTrade Push API Response
 *
 * Per Push API docs, all messages follow this envelope structure:
 * {
 *   "type": "MessageType",
 *   "requestId": "unique-request-id",
 *   "timestamp": 1234567890000,
 *   "session": "session-token",
 *   "payload": { ... }
 * }
 */
readonly class DxtradePushApiResponse
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $type,
        public ?string $requestId = null,
        public int|string|null $timestamp = null,
        public ?string $session = null,
        public array $payload = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'requestId' => $this->requestId,
            'timestamp' => $this->timestamp,
            'session' => $this->session,
            'payload' => $this->payload,
        ];
    }

    /**
     * Parse a Push API response from raw data
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? 'Unknown',
            requestId: $data['requestId'] ?? null,
            timestamp: $data['timestamp'] ?? null,
            session: $data['session'] ?? null,
            payload: $data['payload'] ?? $data, // If no payload key, treat whole message as payload
        );
    }

    /**
     * Check if this is a ping request from the server
     */
    public function isPingRequest(): bool
    {
        return $this->type === 'PingRequest';
    }

    public function isPing(): bool
    {
        return $this->type === 'Ping';
    }

    /**
     * Check if this is a subscription response
     */
    public function isSubscriptionResponse(): bool
    {
        return str_ends_with($this->type, 'SubscriptionResponse');
    }

    public function isSubscriptionClosed(): bool
    {
        return str_ends_with($this->type, 'SubscriptionClosed');
    }

    public function isRequestError(): bool
    {
        return $this->type === 'RequestError';
    }

    public function isReject(): bool
    {
        return str_contains($this->type, 'Reject');
    }

    /**
     * Check if this is an error response
     */
    public function isError(): bool
    {
        return $this->type === 'Error'
            || str_contains($this->type, 'Error')
            || $this->isRequestError()
            || $this->isReject();
    }
}
