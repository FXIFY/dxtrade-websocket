<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Data;

/**
 * DXTrade Push Session Data
 *
 * Holds push session ID and websocket connection details
 * returned from the Notification API push session creation
 */
readonly class DxtradePushSessionData
{
    public function __construct(
        public string $pushSessionId,
        public string $websocketUrl,
        public int $expiresAt,
    ) {}

    public function isExpired(): bool
    {
        return time() >= $this->expiresAt;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'pushSessionId' => $this->pushSessionId,
            'websocketUrl' => $this->websocketUrl,
            'expiresAt' => $this->expiresAt,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            pushSessionId: $data['pushSessionId'] ?? '',
            websocketUrl: $data['websocketUrl'] ?? '',
            expiresAt: $data['expiresAt'] ?? 0,
        );
    }
}
