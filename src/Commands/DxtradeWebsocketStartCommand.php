<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Commands;

use Fxify\DxtradeWebsocket\Actions\StartDxtradeWebsocket;
use Fxify\DxtradeWebsocket\Checkers\RunDxtradeWebsocketChecks;
use Fxify\DxtradeWebsocket\Enums\DxtradeWebsocketChannel;
use Fxify\DxtradeWebsocket\Output\DxtradeWebsocketCommand;
use Illuminate\Console\Command;

class DxtradeWebsocketStartCommand extends Command
{
    public $signature = 'dxtrade:websocket:start {channel}';

    public $description = 'Connects to the DXTrade websocket and dispatches events';

    public function handle(): int
    {
        app(DxtradeWebsocketCommand::class)->setCommand($this);

        $result = app(RunDxtradeWebsocketChecks::class)->run();

        if ($result === false) {
            $this->error('Environment checks failed!');

            return self::FAILURE;
        }

        /** @var string $channelArg */
        $channelArg = $this->argument('channel');

        $channel = DxtradeWebsocketChannel::tryFrom($channelArg);

        if (! $channel) {
            $this->error('Invalid channel provided! Valid channels: accounts, marketData');

            return self::FAILURE;
        }

        app(StartDxtradeWebsocket::class)($channel);

        return self::SUCCESS;
    }
}
