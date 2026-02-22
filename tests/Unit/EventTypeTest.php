<?php

declare(strict_types=1);

use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketEventType;

it('includes subscription closed lifecycle event type', function () {
    expect(DxtradeWebsocketEventType::SubscriptionClosed->value)->toBe('SubscriptionClosed')
        ->and(DxtradeWebsocketEventType::SubscriptionClosed->getLabel())->toBe('Subscription Closed')
        ->and(DxtradeWebsocketEventType::SubscriptionClosed->isProcessable())->toBeFalse();
});
