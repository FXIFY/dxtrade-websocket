<?php

declare(strict_types=1);

use Fxify\DxtradeWebsocket\Support\DxtradeLifecycleEventParser;

it('parses subscription confirmed lifecycle event', function () {
    $event = [
        'type' => 'SubscriptionConfirmed',
        'payload' => [
            'lifecycle' => 'subscription_confirmed',
            'requestId' => 'req-1',
            'responseType' => 'AccountPortfoliosSubscriptionResponse',
            'responsePayload' => ['ok' => true],
            'request' => ['subscription' => 'AccountPortfolios'],
        ],
    ];

    $parsed = DxtradeLifecycleEventParser::parse($event);

    expect($parsed)->not()->toBeNull()
        ->and($parsed?->isSubscriptionConfirmed())->toBeTrue()
        ->and($parsed?->requestId)->toBe('req-1')
        ->and($parsed?->request['subscription'] ?? null)->toBe('AccountPortfolios');
});

it('parses subscription closed lifecycle event', function () {
    $event = [
        'type' => 'SubscriptionClosed',
        'payload' => [
            'lifecycle' => 'subscription_closed',
            'requestId' => 'req-2',
            'responseType' => 'AccountPortfoliosSubscriptionClosed',
            'responsePayload' => ['reason' => 'server_closed'],
        ],
    ];

    $parsed = DxtradeLifecycleEventParser::parse($event);

    expect($parsed)->not()->toBeNull()
        ->and($parsed?->isSubscriptionClosed())->toBeTrue()
        ->and($parsed?->responsePayload['reason'] ?? null)->toBe('server_closed');
});

it('returns null for non lifecycle business events', function () {
    $event = [
        'type' => 'AccountPortfolio',
        'payload' => ['accountId' => 'A-1'],
    ];

    expect(DxtradeLifecycleEventParser::parse($event))->toBeNull();
});
