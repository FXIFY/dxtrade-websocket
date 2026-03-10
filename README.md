# DXTrade Websocket

Provides websocket command and jobs for the DXTrade integration

## Installation

You can install the package via composer:

```bash
composer require fxify/dxtrade-websocket
```

You can publish the config files with:

```bash
php artisan vendor:publish --tag="dxtrade-websocket-config"
```

This will produce the following config files:
- [config/dxtrade-websocket-api.php](config/dxtrade-websocket-api.php)
  - Configure websocket connection settings and subscriptions
- [config/dxtrade-websocket-processor.php](config/dxtrade-websocket-processor.php)
  - Configure the event processor for handling websocket events

## Usage

This package supports being used in any or all of the following scenarios:
- DXTrade websocket event processor
- DXTrade websocket client

## DXTrade websocket event processor

Configure your application's event processor in the [config/dxtrade-websocket-processor.php](config/dxtrade-websocket-processor.php) file:

```php
return [
    'processor' => \App\Services\DXTrade\MyDxtradeEventProcessor::class,
];
```

> [!WARNING]
> The processor must implement the `\Fxify\DxtradeWebsocket\Contracts\Processors\DxtradeWebsocketEventProcessorContract` interface.

> [!NOTE]
> The DXTrade websocket client will dispatch jobs containing the websocket events to this application's queue for processing.

## DXTrade websocket client

### 1. Configure the API

Configure the API settings in the [config/dxtrade-websocket-api.php](config/dxtrade-websocket-api.php) file:

```php
return [
    'default' => [
        'url' => env('DXTRADE_WEBSOCKET_URL'),
        'auth_url' => env('DXTRADE_WEBSOCKET_AUTH_URL', env('DXTRADE_WEBSOCKET_URL')),
        'push_session_url' => env('DXTRADE_WEBSOCKET_PUSH_SESSION_URL', env('DXTRADE_WEBSOCKET_URL')),
        'websocket_url' => env('DXTRADE_WEBSOCKET_DIRECT_URL', env('DXTRADE_WEBSOCKET_URL')),
        'connection_mode' => env('DXTRADE_WEBSOCKET_CONNECTION_MODE', 'push_session'),
        'username' => env('DXTRADE_WEBSOCKET_USERNAME'),
        'password' => env('DXTRADE_WEBSOCKET_PASSWORD'),
        'domain' => env('DXTRADE_WEBSOCKET_DOMAIN', 'default'),
        'clearing_code' => env('DXTRADE_WEBSOCKET_CLEARING_CODE', env('DXTRADE_WEBSOCKET_DOMAIN', 'default')),
        'subscriptions' => [
            'accounts' => true,
            'metrics' => true,
            'events' => true,
            'cash_transfers' => true,
            'instruments' => false,
        ],
    ],
];
```

### 2. Set up environment variables

Add the following to your `.env` file:

```env
# DXTrade Push API base URL
DXTRADE_WEBSOCKET_URL=https://your-dxtrade-server.com

# Optional split URLs
DXTRADE_WEBSOCKET_AUTH_URL=https://your-dxtrade-auth-server.com
DXTRADE_WEBSOCKET_PUSH_SESSION_URL=https://your-dxtrade-push-session-server.com
DXTRADE_WEBSOCKET_DIRECT_URL=https://your-dxtrade-websocket-server.com

# Connection mode
DXTRADE_WEBSOCKET_CONNECTION_MODE=push_session

# Authentication (per DXTrade Token Authentication API)
DXTRADE_WEBSOCKET_USERNAME=your-username
DXTRADE_WEBSOCKET_PASSWORD=your-password
DXTRADE_WEBSOCKET_DOMAIN=default
DXTRADE_WEBSOCKET_CLEARING_CODE=default

# Session settings
DXTRADE_WEBSOCKET_SESSION_TTL_SECONDS=3600

# Subscription settings (accounts channel)
DXTRADE_WEBSOCKET_SUBSCRIBE_ACCOUNTS_ENABLED=true
DXTRADE_WEBSOCKET_SUBSCRIBE_METRICS_ENABLED=true
DXTRADE_WEBSOCKET_SUBSCRIBE_EVENTS_ENABLED=true
DXTRADE_WEBSOCKET_SUBSCRIBE_CASH_TRANSFERS_ENABLED=true

# Subscription settings (marketData channel)
DXTRADE_WEBSOCKET_SUBSCRIBE_INSTRUMENTS_ENABLED=false

# Connection settings
DXTRADE_WEBSOCKET_RECONNECT_DELAY_SECONDS=1.0
DXTRADE_WEBSOCKET_MAX_RECONNECT_ATTEMPTS=10
DXTRADE_WEBSOCKET_MESSAGE_TIMEOUT_SECONDS=60.0
DXTRADE_WEBSOCKET_TIMESTAMP_FORMAT=iso8601

# Heartbeat settings
DXTRADE_WEBSOCKET_HEARTBEAT_ENABLED=false
DXTRADE_WEBSOCKET_HEARTBEAT_INTERVAL_SECONDS=30
```

For DXTrade Push API account subscriptions, the documented payload shape is:
- `requestType: LIST`
- `accounts: ["default:ACCOUNT_CODE"]`

The package now normalizes common account payload shortcuts into DXTrade account keys for:
- `accounts`
- `metrics`
- `events`
- `cash_transfers`

For example, any of the following are normalized to `accounts: ["default:ALCH-DEV1-44"]`:
- `['accountCode' => 'ALCH-DEV1-44']`
- `['accountId' => 'ALCH-DEV1-44']`
- `['accounts' => ['ALCH-DEV1-44']]`
- `['accounts' => [['clearingCode' => 'default', 'accountCode' => 'ALCH-DEV1-44']]]`

You can still provide explicit subscription payloads when needed:

```php
// config/dxtrade-websocket-api.php
'default' => [
    // ...
    'subscription_payloads' => [
        'accounts' => [
            'requestType' => 'LIST',
            'accounts' => ['default:ALCH-DEV1-44'],
        ],
        'metrics' => [
            'requestType' => 'LIST',
            'accounts' => ['default:ALCH-DEV1-44'],
            'includePositions' => true,
        ],
        'events' => [
            'requestType' => 'LIST',
            'accounts' => ['default:ALCH-DEV1-44'],
        ],
        'cash_transfers' => [
            'requestType' => 'LIST',
            'accounts' => ['default:ALCH-DEV1-44'],
        ],
        'instruments' => [
            'types' => ['FOREX'],
            'instruments' => ['EUR/USD'],
        ],
    ],
],
```

### Direct websocket mode

Some DXTrade deployments do not expose `POST /push/session` and instead expect direct websocket connections authenticated with `Authorization: DXAPI <token>`.

For those environments, configure:

```env
DXTRADE_WEBSOCKET_AUTH_URL=https://your-dxtrade-auth-server.com/dxsca-web
DXTRADE_WEBSOCKET_DIRECT_URL=https://your-dxtrade-auth-server.com/dxsca-web
DXTRADE_WEBSOCKET_CONNECTION_MODE=direct
DXTRADE_WEBSOCKET_TIMESTAMP_FORMAT=iso8601
```

In direct mode the package:
- authenticates against `auth_url`
- connects to `wss://...` derived from `websocket_url`
- uses the documented Push API channel paths:
  - `/{?format,...}` for accounts
  - `/md{?format,...}` for market data

### Event payload shapes

DXTrade Push API business responses are collection-based:
- `AccountPortfolios` returns `payload.portfolios`
- `AccountMetrics` returns `payload.metrics`
- `AccountEvents` returns `payload.accountEvents`
- `CashTransfers` returns `payload.cashTransfers`
- `Instruments` returns `payload.instruments`

This package normalizes those collection responses into one processor event per entity. For account events it also derives:
- `accountCode`
- `accountId`
- `clearingCode`

from DXTrade payloads such as `account: "default:ACCOUNT_CODE"` or `owner.login`.

The package now correlates Push `requestId` values for subscription flows and emits lifecycle events to your processor:
- `SubscriptionConfirmed` with `payload.lifecycle = subscription_confirmed`
- `SubscriptionClosed` with `payload.lifecycle = subscription_closed`
- `Error` with `payload.lifecycle = request_rejected|error`

Lifecycle payloads include:
- `requestId`
- `responseType`
- `responsePayload`
- `request` (if matched), containing original `requestType`, `subscription`, and `createdAt`

### Handling lifecycle events in consumer project

In your custom processor, branch first on event `type`, then on `payload.lifecycle` for control flow:

```php
public function process(array $event): void
{
    $type = $event['type'] ?? '';
    $payload = $event['payload'] ?? [];
    $lifecycle = $payload['lifecycle'] ?? null;

    match ($type) {
        'SubscriptionConfirmed' => $this->markSubscriptionReady(
            requestId: $payload['requestId'] ?? null,
            subscription: $payload['request']['subscription'] ?? null,
        ),
        'SubscriptionClosed' => $this->handleClosedSubscription(
            requestId: $payload['requestId'] ?? null,
            reason: $payload['responsePayload'] ?? [],
        ),
        'Error' => $this->handleProtocolError(
            lifecycle: $lifecycle,
            requestId: $payload['requestId'] ?? null,
            details: $payload['responsePayload'] ?? [],
        ),
        default => $this->handleBusinessEvent($event), // account/metric/event/cash/instrument
    };
}
```

Or use the helper parser from this package:

```php
use Fxify\DxtradeWebsocket\Support\DxtradeLifecycleEventParser;

public function process(array $event): void
{
    $lifecycle = DxtradeLifecycleEventParser::parse($event);

    if ($lifecycle?->isSubscriptionConfirmed()) {
        // Ready to consume business events for this subscription
        return;
    }

    if ($lifecycle?->isSubscriptionClosed()) {
        // Trigger reconnect/re-subscribe workflow
        return;
    }

    if ($lifecycle?->isRequestRejected() || $lifecycle?->isProtocolError()) {
        // Alert/log; decide retry policy
        return;
    }

    // Normal business event
}
```

Or use the shorter wrapper:

```php
use Fxify\DxtradeWebsocket\Support\DxtradeLifecycle;

$lifecycle = DxtradeLifecycle::from($event);

if ($lifecycle?->isSubscriptionClosed()) {
    // handle closure
}
```

### 3. Run the test command

Test your websocket connection for the accounts channel:

```sh
php artisan dxtrade:websocket:test accounts
```

Or for the market data channel:

```sh
php artisan dxtrade:websocket:test marketData
```

### 4. Start the websocket client

Start listening for events on the accounts channel:

```sh
php artisan dxtrade:websocket:start accounts
```

Or for the market data channel:

```sh
php artisan dxtrade:websocket:start marketData
```

> [!NOTE]
> For production use, it's recommended to run this with a process manager like Supervisor.

## Creating a Custom Event Processor

Create a custom processor by implementing the contract:

```php
<?php

namespace App\Services\DXTrade;

use Fxify\DxtradeWebsocket\Contracts\Processors\DxtradeWebsocketEventProcessorContract;

class MyDxtradeEventProcessor implements DxtradeWebsocketEventProcessorContract
{
    public function process(array $event): void
    {
        // Handle the websocket event
        $eventType = $event['type'] ?? 'unknown';

        match($eventType) {
            'AccountPortfolio' => $this->handleAccountPortfolio($event),
            'AccountMetric' => $this->handleAccountMetric($event),
            'AccountEvent' => $this->handleAccountEvent($event),
            'CashTransfer' => $this->handleCashTransfer($event),
            'InstrumentInfo' => $this->handleInstrumentInfo($event),
            default => logger()->warning('Unknown DXTrade event type', ['event' => $event]),
        };
    }

    private function handleAccountPortfolio(array $event): void
    {
        // Your logic here
    }

    private function handleAccountMetric(array $event): void
    {
        // Your logic here
    }

    private function handleAccountEvent(array $event): void
    {
        // Your logic here
    }

    private function handleCashTransfer(array $event): void
    {
        // Your logic here
    }

    private function handleInstrumentInfo(array $event): void
    {
        // Your logic here
    }
}
```

## Supervisor Configuration Example

```ini
[program:dxtrade-websocket]
process_name=%(program_name)s
command=php /path/to/your/application/artisan dxtrade:websocket:start accounts
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=your-user
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/application/storage/logs/dxtrade-websocket.log
stopwaitsecs=3600
```

## Requirements

- PHP 8.3 or higher
- Laravel 11.0 or higher
- ext-swoole (recommended for websocket client)

## Testing

```bash
composer test
```

## Code Quality

```bash
# Run code formatting
composer format

# Run static analysis
composer analyse

# Run all checks
composer check
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [FXIFY](https://github.com/fxify)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
