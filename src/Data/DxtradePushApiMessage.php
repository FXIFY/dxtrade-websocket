<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Data;

/**
 * DXTrade Push API Message
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
readonly class DxtradePushApiMessage
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $type,
        public string $requestId,
        public int $timestamp,
        public string $session,
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

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Create a message with auto-generated requestId and timestamp
     *
     * @param  array<string, mixed>  $payload
     */
    public static function create(
        string $type,
        string $session,
        array $payload = []
    ): self {
        return new self(
            type: $type,
            requestId: self::generateRequestId(),
            timestamp: (int) (microtime(true) * 1000), // milliseconds since epoch
            session: $session,
            payload: $payload,
        );
    }

    private static function generateRequestId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
