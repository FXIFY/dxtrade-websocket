<?php

declare(strict_types=1);

use Fxify\DxtradeWebsocket\Support\DxtradeLifecycle;

it('parses lifecycle events via wrapper', function () {
    $event = [
        'type' => 'SubscriptionConfirmed',
        'payload' => [
            'lifecycle' => 'subscription_confirmed',
            'requestId' => 'req-1',
            'responseType' => 'AccountPortfoliosSubscriptionResponse',
            'responsePayload' => ['ok' => true],
        ],
    ];

    $parsed = DxtradeLifecycle::from($event);

    expect($parsed)->not()->toBeNull()
        ->and($parsed?->isSubscriptionConfirmed())->toBeTrue()
        ->and($parsed?->requestId)->toBe('req-1');
});
