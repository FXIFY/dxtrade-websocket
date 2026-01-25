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
        'username' => env('DXTRADE_WEBSOCKET_USERNAME'),
        'password' => env('DXTRADE_WEBSOCKET_PASSWORD'),
        'subscriptions' => [
            'accounts' => true,
            'orders' => true,
            'positions' => true,
            // ... other subscriptions
        ],
    ],
];
```

### 2. Set up environment variables

Add the following to your `.env` file:

```env
DXTRADE_WEBSOCKET_URL=wss://your-dxtrade-server/websocket
DXTRADE_WEBSOCKET_USERNAME=your-username
DXTRADE_WEBSOCKET_PASSWORD=your-password

# Subscription settings
DXTRADE_WEBSOCKET_SUBSCRIBE_ACCOUNTS_ENABLED=true
DXTRADE_WEBSOCKET_SUBSCRIBE_ORDERS_ENABLED=true
DXTRADE_WEBSOCKET_SUBSCRIBE_POSITIONS_ENABLED=true
DXTRADE_WEBSOCKET_SUBSCRIBE_TRADES_ENABLED=true
DXTRADE_WEBSOCKET_SUBSCRIBE_QUOTES_ENABLED=false

# Connection settings
DXTRADE_WEBSOCKET_RECONNECT_DELAY_SECONDS=1.0
DXTRADE_WEBSOCKET_MAX_RECONNECT_ATTEMPTS=10
DXTRADE_WEBSOCKET_MESSAGE_TIMEOUT_SECONDS=60.0

# Heartbeat settings
DXTRADE_WEBSOCKET_HEARTBEAT_ENABLED=true
DXTRADE_WEBSOCKET_HEARTBEAT_INTERVAL_SECONDS=30
```

### 3. Run the test command

Test your websocket connection:

```sh
php artisan dxtrade:websocket:test default
```

### 4. Start the websocket client

Start listening for events:

```sh
php artisan dxtrade:websocket:start default
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
            'account_update' => $this->handleAccountUpdate($event),
            'order_update' => $this->handleOrderUpdate($event),
            'position_update' => $this->handlePositionUpdate($event),
            default => logger()->warning('Unknown DXTrade event type', ['event' => $event]),
        };
    }

    private function handleAccountUpdate(array $event): void
    {
        // Your logic here
    }

    private function handleOrderUpdate(array $event): void
    {
        // Your logic here
    }

    private function handlePositionUpdate(array $event): void
    {
        // Your logic here
    }
}
```

## Supervisor Configuration Example

```ini
[program:dxtrade-websocket]
process_name=%(program_name)s
command=php /path/to/your/application/artisan dxtrade:websocket:start default
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
