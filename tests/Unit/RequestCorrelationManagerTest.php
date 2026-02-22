<?php

declare(strict_types=1);

use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketSubscription;
use Fxify\DxtradeWebsocket\Services\DxtradePushRequestCorrelationManager;

it('tracks pending and active subscription requests', function () {
    $manager = new DxtradePushRequestCorrelationManager();

    $manager->registerSubscriptionRequest(
        requestId: 'req-1',
        requestType: 'AccountPortfoliosSubscriptionRequest',
        subscription: DxtradeWebsocketSubscription::AccountPortfolios,
    );

    expect($manager->getPendingCount())->toBe(1)
        ->and($manager->getActiveCount())->toBe(0);

    $context = $manager->confirmSubscription('req-1');

    expect($context)->not()->toBeNull()
        ->and($context?->requestId)->toBe('req-1')
        ->and($manager->getPendingCount())->toBe(0)
        ->and($manager->getActiveCount())->toBe(1);
});

it('removes pending request on failure', function () {
    $manager = new DxtradePushRequestCorrelationManager();

    $manager->registerSubscriptionRequest(
        requestId: 'req-2',
        requestType: 'AccountEventsSubscriptionRequest',
        subscription: DxtradeWebsocketSubscription::AccountEvents,
    );

    $context = $manager->failRequest('req-2');

    expect($context)->not()->toBeNull()
        ->and($context?->subscription)->toBe('AccountEvents')
        ->and($manager->getPendingCount())->toBe(0)
        ->and($manager->getActiveCount())->toBe(0);
});

it('closes active subscription and clears it', function () {
    $manager = new DxtradePushRequestCorrelationManager();

    $manager->registerSubscriptionRequest(
        requestId: 'req-3',
        requestType: 'AccountMetricsSubscriptionRequest',
        subscription: DxtradeWebsocketSubscription::AccountMetrics,
    );

    $manager->confirmSubscription('req-3');
    $context = $manager->closeSubscription('req-3');

    expect($context)->not()->toBeNull()
        ->and($context?->requestId)->toBe('req-3')
        ->and($manager->getPendingCount())->toBe(0)
        ->and($manager->getActiveCount())->toBe(0);
});
