<?php

declare(strict_types=1);

use Fxify\DxtradeWebsocket\Data\DxtradePushApiResponse;

it('detects ping and ping request messages', function () {
    $pingRequest = DxtradePushApiResponse::fromArray(['type' => 'PingRequest']);
    $ping = DxtradePushApiResponse::fromArray(['type' => 'Ping']);

    expect($pingRequest->isPingRequest())->toBeTrue()
        ->and($pingRequest->isPing())->toBeFalse()
        ->and($ping->isPing())->toBeTrue()
        ->and($ping->isPingRequest())->toBeFalse();
});

it('detects subscription lifecycle messages', function () {
    $response = DxtradePushApiResponse::fromArray(['type' => 'AccountPortfoliosSubscriptionResponse']);
    $closed = DxtradePushApiResponse::fromArray(['type' => 'AccountPortfoliosSubscriptionClosed']);

    expect($response->isSubscriptionResponse())->toBeTrue()
        ->and($response->isSubscriptionClosed())->toBeFalse()
        ->and($closed->isSubscriptionClosed())->toBeTrue()
        ->and($closed->isSubscriptionResponse())->toBeFalse();
});

it('detects request errors and rejects as errors', function () {
    $requestError = DxtradePushApiResponse::fromArray(['type' => 'RequestError']);
    $reject = DxtradePushApiResponse::fromArray(['type' => 'AccountPortfoliosSubscriptionReject']);

    expect($requestError->isRequestError())->toBeTrue()
        ->and($requestError->isError())->toBeTrue()
        ->and($reject->isReject())->toBeTrue()
        ->and($reject->isError())->toBeTrue();
});
