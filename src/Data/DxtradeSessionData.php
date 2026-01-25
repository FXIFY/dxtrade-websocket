<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Data;

/**
 * DXTrade Session Data
 *
 * Holds session token and related metadata for DXTrade API authentication
 */
readonly class DxtradeSessionData
{
    public function __construct(
        public string $sessionToken,
        public int $expiresAt,
        public ?string $userId = null,
    ) {}

    public function isExpired(): bool
    {
        return time() >= $this->expiresAt;
    }

    public function isExpiringSoon(int $bufferSeconds = 60): bool
    {
        return time() >= ($this->expiresAt - $bufferSeconds);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'sessionToken' => $this->sessionToken,
            'expiresAt' => $this->expiresAt,
            'userId' => $this->userId,
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            sessionToken: $data['sessionToken'] ?? '',
            expiresAt: $data['expiresAt'] ?? 0,
            userId: $data['userId'] ?? null,
        );
    }
}
