<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Exceptions;

use Exception;

class DxtradeWebsocketException extends Exception
{
    public static function connectionFailed(string $reason): self
    {
        return new self("DXTrade websocket connection failed: {$reason}");
    }

    public static function subscriptionFailed(string $subscription, string $reason): self
    {
        return new self("DXTrade websocket subscription to {$subscription} failed: {$reason}");
    }

    public static function invalidMessage(string $reason): self
    {
        return new self("Invalid DXTrade websocket message: {$reason}");
    }
}
