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
use Fxify\DxtradeWebsocket\Services\DxtradePushRequestCorrelationManager;
use Fxify\DxtradeWebsocket\Services\DxtradeSessionManager;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Http\Client\Factory as HttpFactory;

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
        $this->app->singleton(DxtradePushRequestCorrelationManager::class);

        $this->app->bind(DxtradeWebsocketEventProcessorContract::class, function () {
            $processorClass = config('dxtrade-websocket-processor.processor');

            if (blank($processorClass) || ! is_string($processorClass)) {
                $processorClass = DefaultDxtradeWebsocketEventProcessor::class;
            }

            return app($processorClass);
        });

        // Bind session manager
        $this->app->singleton(DxtradeSessionManager::class, function () {
            return new DxtradeSessionManager(
                http: app(HttpFactory::class),
                baseUrl: config('dxtrade-websocket-api.default.url'),
                username: config('dxtrade-websocket-api.default.username'),
                password: config('dxtrade-websocket-api.default.password'),
                domain: config('dxtrade-websocket-api.default.domain'),
                sessionTtl: config('dxtrade-websocket-api.session_ttl', 3600),
            );
        });
    }

    /** @return array<string> */
    public function provides(): array
    {
        return [
            DxtradeWebsocketCoroutineManager::class,
            DxtradeWebsocketCommand::class,
            DxtradeWebsocketEventProcessorContract::class,
            DxtradePushRequestCorrelationManager::class,
            DxtradeSessionManager::class,
        ];
    }
}
