<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Enums;

/**
 * DXTrade WebSocket Subscription Types
 *
 * Available subscriptions on the accounts channel:
 * - AccountPortfolios: Consolidated balances/margins/equity (can include positions/orders)
 * - AccountMetrics: Focused equity/margin ticks
 * - AccountEvents: Trade/order/cash lifecycle events
 * - CashTransfers: Deposit/withdrawal/adjustment ledger
 * - InstrumentInfo: Symbol metadata updates (market data channel)
 */
enum DxtradeWebsocketSubscription: string
{
    case AccountPortfolios = 'AccountPortfolios';
    case AccountMetrics = 'AccountMetrics';
    case AccountEvents = 'AccountEvents';
    case CashTransfers = 'CashTransfers';
    case InstrumentInfo = 'InstrumentInfo';

    public function getChannel(): DxtradeWebsocketChannel
    {
        return match ($this) {
            self::AccountPortfolios,
            self::AccountMetrics,
            self::AccountEvents,
            self::CashTransfers => DxtradeWebsocketChannel::Accounts,
            self::InstrumentInfo => DxtradeWebsocketChannel::MarketData,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::AccountPortfolios => 'Account Portfolios',
            self::AccountMetrics => 'Account Metrics',
            self::AccountEvents => 'Account Events',
            self::CashTransfers => 'Cash Transfers',
            self::InstrumentInfo => 'Instrument Info',
        };
    }

    /**
     * Get the Push API request type for this subscription
     * Per Push API docs, subscription requests follow the pattern: {Type}SubscriptionRequest
     */
    public function getRequestType(): string
    {
        return match ($this) {
            self::AccountPortfolios => 'AccountPortfoliosSubscriptionRequest',
            self::AccountMetrics => 'AccountMetricsSubscriptionRequest',
            self::AccountEvents => 'AccountEventsSubscriptionRequest',
            self::CashTransfers => 'CashTransfersSubscriptionRequest',
            self::InstrumentInfo => 'InstrumentSubscriptionRequest',
        };
    }
}
