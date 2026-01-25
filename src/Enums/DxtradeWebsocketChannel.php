<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Enums;

/**
 * DXTrade WebSocket Channels
 *
 * DXTrade supports two main websocket channels via Push API:
 * - accounts: Account data, portfolios, metrics, events, cash transfers (path: /)
 * - marketData: Instrument info and market data updates (path: /md)
 *
 * Per Push API docs:
 * - Connection URI: /?format={format}&compression={compression} for accounts
 * - Connection URI: /md?format={format}&compression={compression} for market data
 */
enum DxtradeWebsocketChannel: string
{
    case Accounts = 'accounts';
    case MarketData = 'marketData';

    /**
     * Get the base path for this channel
     * Per Push API: / for accounts, /md for market data
     */
    public function getBasePath(): string
    {
        return match ($this) {
            self::Accounts => '/',
            self::MarketData => '/md',
        };
    }

    /**
     * Get the full path with query parameters
     *
     * @param string|null $format Format (json or xml, default: json)
     * @param string|null $compression Compression type (gzip or null)
     */
    public function getPath(?string $format = 'json', ?string $compression = null): string
    {
        $basePath = $this->getBasePath();
        $queryParams = [];

        if ($format) {
            $queryParams['format'] = $format;
        }

        if ($compression) {
            $queryParams['compression'] = $compression;
        }

        if (empty($queryParams)) {
            return $basePath;
        }

        return $basePath . '?' . http_build_query($queryParams);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Accounts => 'Accounts Channel',
            self::MarketData => 'Market Data Channel',
        };
    }
}
