<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Checkers;

use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;

class RunDxtradeWebsocketChecks
{
    use DxtradeWebsocketProvidesCommandOutput;

    public function run(): bool
    {
        $this->info('Running environment checks...');

        // Check if Swoole extension is loaded
        if (! extension_loaded('swoole')) {
            $this->error('Swoole extension is not loaded. Please install swoole extension.');

            return false;
        }

        $this->info('✓ Swoole extension is loaded');

        // Check configuration
        $baseUrl = config('dxtrade-websocket-api.default.url');
        if (empty($baseUrl)) {
            $this->error('DXTRADE_WEBSOCKET_URL is not configured');

            return false;
        }

        $this->info("✓ Base URL configured: {$baseUrl}");

        $username = config('dxtrade-websocket-api.default.username');
        if (empty($username)) {
            $this->error('DXTRADE_WEBSOCKET_USERNAME is not configured');

            return false;
        }

        $this->info("✓ Username configured: {$username}");

        $password = config('dxtrade-websocket-api.default.password');
        if (empty($password)) {
            $this->error('DXTRADE_WEBSOCKET_PASSWORD is not configured');

            return false;
        }

        $this->info('✓ Password is configured');

        // Check subscriptions
        $subscriptions = config('dxtrade-websocket-api.default.subscriptions', []);
        $enabledCount = count(array_filter($subscriptions));

        if ($enabledCount === 0) {
            $this->error('No subscriptions are enabled in configuration');

            return false;
        }

        $this->info("✓ {$enabledCount} subscriptions enabled");

        $this->info('All checks passed!');

        return true;
    }
}
