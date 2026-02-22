<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Enums;

/**
 * DXTrade WebSocket Event Types
 *
 * Maps to the different event types that can be received from DXTrade websocket subscriptions.
 *
 * Per Push API, incoming messages have types like:
 * - AccountPortfolioUpdate, AccountPortfolioSnapshot, etc.
 * - AccountMetricUpdate, AccountMetricSnapshot, etc.
 * - AccountEventUpdate, AccountEventSnapshot, etc.
 * - CashTransferUpdate, CashTransferSnapshot, etc.
 * - InstrumentUpdate, InstrumentSnapshot, etc.
 *
 * These are normalized to internal event types (without Update/Snapshot suffix)
 * for consistent processing across the application.
 */
enum DxtradeWebsocketEventType: string
{
    case AccountPortfolio = 'AccountPortfolio';
    case AccountMetric = 'AccountMetric';
    case AccountEvent = 'AccountEvent';
    case CashTransfer = 'CashTransfer';
    case InstrumentInfo = 'InstrumentInfo';
    case Error = 'Error';
    case ConnectionEstablished = 'ConnectionEstablished';
    case SubscriptionConfirmed = 'SubscriptionConfirmed';
    case SubscriptionClosed = 'SubscriptionClosed';
    case Heartbeat = 'Heartbeat';

    public function getLabel(): string
    {
        return match ($this) {
            self::AccountPortfolio => 'Account Portfolio',
            self::AccountMetric => 'Account Metric',
            self::AccountEvent => 'Account Event',
            self::CashTransfer => 'Cash Transfer',
            self::InstrumentInfo => 'Instrument Info',
            self::Error => 'Error',
            self::ConnectionEstablished => 'Connection Established',
            self::SubscriptionConfirmed => 'Subscription Confirmed',
            self::SubscriptionClosed => 'Subscription Closed',
            self::Heartbeat => 'Heartbeat',
        };
    }

    public function isProcessable(): bool
    {
        return match ($this) {
            self::AccountPortfolio,
            self::AccountMetric,
            self::AccountEvent,
            self::CashTransfer,
            self::InstrumentInfo => true,
            default => false,
        };
    }
}
