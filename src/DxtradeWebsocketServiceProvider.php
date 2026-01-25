<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket;

use Carbon\Laravel\ServiceProvider;
use Fxify\DxtradeWebsocket\Commands\DxtradeWebsocketStartCommand;
use Fxify\DxtradeWebsocket\Commands\DxtradeWebsocketTestCommand;
use Fxify\DxtradeWebsocket\Contracts\Processors\DxtradeWebsocketEventProcessorContract;
use Fxify\DxtradeWebsocket\Managers\DxtradeWebsocketCoroutineManager;
use Fxify\DxtradeWebsocket\Output\DxtradeWebsocketCommand;
use Fxify\DxtradeWebsocket\Processors\DefaultDxtradeWebsocketEventProcessor;
use Illuminate\Contracts\Support\DeferrableProvider;

class DxtradeWebsocketServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/dxtrade-websocket-api.php' => config_path('dxtrade-websocket-api.php'),
                __DIR__ . '/../config/dxtrade-websocket-processor.php' => config_path('dxtrade-websocket-processor.php'),
            ], 'dxtrade-websocket-config');

            $this->commands([
                DxtradeWebsocketStartCommand::class,
                DxtradeWebsocketTestCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/dxtrade-websocket-api.php', 'dxtrade-websocket-api');
        $this->mergeConfigFrom(__DIR__ . '/../config/dxtrade-websocket-processor.php', 'dxtrade-websocket-processor');

        $this->app->scoped(DxtradeWebsocketCoroutineManager::class);
        $this->app->scoped(DxtradeWebsocketCommand::class, fn () => new DxtradeWebsocketCommand());

        $this->app->bind(DxtradeWebsocketEventProcessorContract::class, function () {
            $processorClass = config('dxtrade-websocket-processor.processor');

            if (blank($processorClass) || ! is_string($processorClass)) {
                $processorClass = DefaultDxtradeWebsocketEventProcessor::class;
            }

            return app($processorClass);
        });
    }

    /** @return array<string> */
    public function provides(): array
    {
        return [
            DxtradeWebsocketCoroutineManager::class,
            DxtradeWebsocketCommand::class,
            DxtradeWebsocketEventProcessorContract::class,
        ];
    }
}
