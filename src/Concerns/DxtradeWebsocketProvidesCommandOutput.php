<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Concerns;

use Fxify\DxtradeWebsocket\Output\DxtradeWebsocketCommand;
use Throwable;

trait DxtradeWebsocketProvidesCommandOutput
{
    protected function info(string $message): void
    {
        app(DxtradeWebsocketCommand::class)->info($message);
    }

    protected function error(string $message): void
    {
        app(DxtradeWebsocketCommand::class)->error($message);
    }

    protected function comment(string $message): void
    {
        app(DxtradeWebsocketCommand::class)->comment($message);
    }

    protected function line(string $message): void
    {
        app(DxtradeWebsocketCommand::class)->line($message);
    }

    protected function report(Throwable $exception): void
    {
        report($exception);
    }
}
