<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Exceptions;

use Exception;

class DxtradeSessionException extends Exception
{
    public static function loginFailed(string $reason): self
    {
        return new self("DXTrade login failed: {$reason}");
    }

    public static function sessionExpired(): self
    {
        return new self('DXTrade session has expired');
    }

    public static function pingFailed(string $reason): self
    {
        return new self("DXTrade session ping failed: {$reason}");
    }

    public static function pushSessionCreationFailed(string $reason): self
    {
        return new self("DXTrade push session creation failed: {$reason}");
    }
}
