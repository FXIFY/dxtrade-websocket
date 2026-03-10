<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Coroutines;

use Fxify\DxtradeWebsocket\Clients\DxtradeWebsocketClient;
use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;
use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketSubscription;
use Fxify\DxtradeWebsocket\Managers\DxtradeWebsocketCoroutineManager;
class DxtradeWebsocketSubscribeToEnabledSubscriptionsCoroutine
{
    use DxtradeWebsocketProvidesCommandOutput;

    public function __construct(
        private DxtradeWebsocketCoroutineManager $coroutineManager,
        private DxtradeWebsocketSubscribeToSubscriptionCoroutine $subscribeToSubscriptionCoroutine,
    ) {}

    public function start(DxtradeWebsocketClient $client): void
    {
        $channel = $client->getChannel();
        $sessionToken = $client->getSessionToken();

        if (! $sessionToken) {
            $this->error('No session token available for subscriptions');

            return;
        }

        // Get enabled subscriptions from config
        $subscriptions = config('dxtrade-websocket-api.default.subscriptions', []);

        foreach (DxtradeWebsocketSubscription::cases() as $subscription) {
            // Only subscribe if it's for the current channel and enabled in config
            if ($subscription->getChannel() !== $channel) {
                continue;
            }

            $configKey = $this->getSubscriptionConfigKey($subscription);
            if (! ($subscriptions[$configKey] ?? false)) {
                continue;
            }

            $payload = $this->getSubscriptionPayload($subscription);

            $this->subscribeToSubscriptionCoroutine->handle($client, $subscription, $sessionToken, $payload);
        }
    }

    private function getSubscriptionConfigKey(DxtradeWebsocketSubscription $subscription): string
    {
        return match ($subscription) {
            DxtradeWebsocketSubscription::AccountPortfolios => 'accounts',
            DxtradeWebsocketSubscription::AccountMetrics => 'metrics',
            DxtradeWebsocketSubscription::AccountEvents => 'events',
            DxtradeWebsocketSubscription::CashTransfers => 'cash_transfers',
            DxtradeWebsocketSubscription::InstrumentInfo => 'instruments',
        };
    }

    /** @return array<string, mixed> */
    private function getSubscriptionPayload(DxtradeWebsocketSubscription $subscription): array
    {
        $configKey = $this->getSubscriptionConfigKey($subscription);
        $payload = config("dxtrade-websocket-api.default.subscription_payloads.{$configKey}", []);

        if (! is_array($payload)) {
            $this->error("Invalid payload config for {$subscription->value}; expected array");

            return [];
        }

        return $this->normalizeSubscriptionPayload($subscription, $payload);
    }

    /** @param array<string, mixed> $payload */
    private function normalizeSubscriptionPayload(DxtradeWebsocketSubscription $subscription, array $payload): array
    {
        return match ($subscription) {
            DxtradeWebsocketSubscription::AccountPortfolios,
            DxtradeWebsocketSubscription::AccountMetrics,
            DxtradeWebsocketSubscription::AccountEvents,
            DxtradeWebsocketSubscription::CashTransfers => $this->normalizeAccountSubscriptionPayload($payload),
            default => $payload,
        };
    }

    /** @param array<string, mixed> $payload */
    private function normalizeAccountSubscriptionPayload(array $payload): array
    {
        if (! array_key_exists('requestType', $payload)) {
            $payload['requestType'] = 'LIST';
        }

        $accounts = $this->extractAccountKeys($payload);

        if ($accounts !== null) {
            $payload['accounts'] = $accounts;
        }

        unset($payload['accountCode'], $payload['accountId'], $payload['accountCodes'], $payload['accountIds']);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, string>|null
     */
    private function extractAccountKeys(array $payload): ?array
    {
        $rawAccounts = $payload['accounts'] ?? null;

        if ($rawAccounts === null) {
            $rawAccounts = [];

            foreach (['accountCode', 'accountId'] as $key) {
                if (filled($payload[$key] ?? null)) {
                    $rawAccounts[] = $payload[$key];
                }
            }

            foreach (['accountCodes', 'accountIds'] as $key) {
                if (is_array($payload[$key] ?? null)) {
                    $rawAccounts = array_merge($rawAccounts, $payload[$key]);
                }
            }
        }

        if (is_string($rawAccounts)) {
            $rawAccounts = [$rawAccounts];
        }

        if (! is_array($rawAccounts)) {
            return null;
        }

        $accounts = [];

        foreach ($rawAccounts as $account) {
            $normalized = $this->normalizeAccountKey($account);

            if ($normalized !== null) {
                $accounts[] = $normalized;
            }
        }

        return array_values(array_unique($accounts));
    }

    private function normalizeAccountKey(mixed $account): ?string
    {
        if (is_string($account)) {
            $account = trim($account);

            if ($account === '') {
                return null;
            }

            return str_contains($account, ':')
                ? $account
                : "{$this->getDefaultClearingCode()}:{$account}";
        }

        if (! is_array($account)) {
            return null;
        }

        $compositeAccount = $account['account'] ?? null;

        if (is_string($compositeAccount) && $compositeAccount !== '') {
            return $compositeAccount;
        }

        $clearingCode = $account['clearingCode'] ?? $this->getDefaultClearingCode();
        $accountCode = $account['accountCode'] ?? $account['accountId'] ?? null;

        if (! is_string($clearingCode) || $clearingCode === '') {
            return null;
        }

        if (! is_string($accountCode) || $accountCode === '') {
            return null;
        }

        return "{$clearingCode}:{$accountCode}";
    }

    private function getDefaultClearingCode(): string
    {
        return (string) config(
            'dxtrade-websocket-api.default.clearing_code',
            config('dxtrade-websocket-api.default.domain', 'default')
        );
    }
}
