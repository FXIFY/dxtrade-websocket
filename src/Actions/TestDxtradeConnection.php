<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Actions;

use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;
use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketChannel;
use Fxify\DxtradeWebsocket\Services\DxtradeSessionManager;
use Throwable;

class TestDxtradeConnection
{
    use DxtradeWebsocketProvidesCommandOutput;

    public function __construct(
        private DxtradeSessionManager $sessionManager,
    ) {}

    public function __invoke(DxtradeWebsocketChannel $channel): bool
    {
        $this->info("Testing DXTrade connection for {$channel->getLabel()}...");

        try {
            // Test login
            $this->comment('Step 1: Testing authentication...');
            $session = $this->sessionManager->login();
            $this->info("✓ Login successful. Session token: {$session->sessionToken}");

            // Test ping
            $this->comment('Step 2: Testing session ping...');
            $this->sessionManager->ping();
            $this->info('✓ Session ping successful');

            // Test push session creation
            $this->comment('Step 3: Testing push session creation...');
            $pushSession = $this->sessionManager->createPushSession($channel);
            $this->info("✓ Push session created successfully");
            $this->info("  Push Session ID: {$pushSession->pushSessionId}");
            $this->info("  WebSocket URL: {$pushSession->websocketUrl}");

            // Clean up
            $this->comment('Step 4: Cleaning up...');
            $this->sessionManager->logout();
            $this->info('✓ Logout successful');

            $this->info('');
            $this->info('✅ All connection tests passed!');

            return true;

        } catch (Throwable $e) {
            $this->report($e);
            $this->error("❌ Connection test failed: {$e->getMessage()}");

            return false;
        }
    }
}
