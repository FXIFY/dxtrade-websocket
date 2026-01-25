<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Data;

use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketEventType;

/**
 * DXTrade WebSocket Event Data
 *
 * Represents a normalized websocket event from DXTrade
 */
readonly class DxtradeWebsocketEventData
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public DxtradeWebsocketEventType $type,
        public array $payload,
        public ?string $accountId = null,
        public ?int $timestamp = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'payload' => $this->payload,
            'accountId' => $this->accountId,
            'timestamp' => $this->timestamp ?? time(),
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            type: DxtradeWebsocketEventType::tryFrom($data['type'] ?? '') ?? DxtradeWebsocketEventType::Error,
            payload: $data['payload'] ?? [],
            accountId: $data['accountId'] ?? null,
            timestamp: $data['timestamp'] ?? null,
        );
    }
}
