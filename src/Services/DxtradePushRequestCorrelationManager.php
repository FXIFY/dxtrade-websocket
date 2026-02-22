<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Services;

use Fxify\DxtradeWebsocket\Data\DxtradePushRequestContext;
use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketSubscription;

class DxtradePushRequestCorrelationManager
{
    /** @var array<string, DxtradePushRequestContext> */
    private array $pendingRequests = [];

    /** @var array<string, DxtradePushRequestContext> */
    private array $activeSubscriptions = [];

    public function registerSubscriptionRequest(
        string $requestId,
        string $requestType,
        DxtradeWebsocketSubscription $subscription
    ): void {
        $this->pendingRequests[$requestId] = new DxtradePushRequestContext(
            requestId: $requestId,
            requestType: $requestType,
            subscription: $subscription->value,
            createdAt: (int) (microtime(true) * 1000),
        );
    }

    public function unregisterRequest(string $requestId): ?DxtradePushRequestContext
    {
        if (! isset($this->pendingRequests[$requestId])) {
            return null;
        }

        $context = $this->pendingRequests[$requestId];
        unset($this->pendingRequests[$requestId]);

        return $context;
    }

    public function confirmSubscription(string $requestId): ?DxtradePushRequestContext
    {
        $context = $this->unregisterRequest($requestId);
        if (! $context) {
            return null;
        }

        $this->activeSubscriptions[$requestId] = $context;

        return $context;
    }

    public function failRequest(string $requestId): ?DxtradePushRequestContext
    {
        return $this->unregisterRequest($requestId);
    }

    public function closeSubscription(string $requestId): ?DxtradePushRequestContext
    {
        if (isset($this->activeSubscriptions[$requestId])) {
            $context = $this->activeSubscriptions[$requestId];
            unset($this->activeSubscriptions[$requestId]);

            return $context;
        }

        return $this->unregisterRequest($requestId);
    }

    public function getPendingCount(): int
    {
        return count($this->pendingRequests);
    }

    public function getActiveCount(): int
    {
        return count($this->activeSubscriptions);
    }

    public function clear(): void
    {
        $this->pendingRequests = [];
        $this->activeSubscriptions = [];
    }
}
