<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Tests;

use Fxify\DxtradeWebsocket\DxtradeWebsocketServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            DxtradeWebsocketServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }
}
