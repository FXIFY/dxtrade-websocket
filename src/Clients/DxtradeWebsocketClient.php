<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Clients;

use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketChannel;
use Swoole\Coroutine\Http\Client;

class DxtradeWebsocketClient extends Client
{
    private ?string $sessionToken = null;

    public function __construct(
        private DxtradeWebsocketChannel $channel,
        string $host,
        int $port,
        bool $ssl,
    ) {
        parent::__construct($host, $port, $ssl);
    }

    public function getChannel(): DxtradeWebsocketChannel
    {
        return $this->channel;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function setSessionToken(string $sessionToken): void
    {
        $this->sessionToken = $sessionToken;
    }

    public function getSessionToken(): ?string
    {
        return $this->sessionToken;
    }

    public function getClientStatusSummary(): string
    {
        return implode(' / ', [
            "Channel: {$this->channel->value}",
            "Status: {$this->statusCode}",
            "Error Code: {$this->errCode}",
            "Error Message: {$this->errMsg}",
        ]);
    }
}
