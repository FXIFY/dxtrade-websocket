<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Data;

readonly class DxtradePushRequestContext
{
    public function __construct(
        public string $requestId,
        public string $requestType,
        public string $subscription,
        public int $createdAt,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'requestId' => $this->requestId,
            'requestType' => $this->requestType,
            'subscription' => $this->subscription,
            'createdAt' => $this->createdAt,
        ];
    }
}
