<?php

declare(strict_types=1);

use Fxify\DxtradeWebsocket\Contracts\Processors\DxtradeWebsocketEventProcessorContract;
use Fxify\DxtradeWebsocket\Managers\DxtradeWebsocketCoroutineManager;
use Fxify\DxtradeWebsocket\Output\DxtradeWebsocketCommand;
use Fxify\DxtradeWebsocket\Processors\DefaultDxtradeWebsocketEventProcessor;

it('registers the coroutine manager', function () {
    $manager = app(DxtradeWebsocketCoroutineManager::class);

    expect($manager)->toBeInstanceOf(DxtradeWebsocketCoroutineManager::class);
});

it('registers the command output', function () {
    $command = app(DxtradeWebsocketCommand::class);

    expect($command)->toBeInstanceOf(DxtradeWebsocketCommand::class);
});

it('registers the default event processor', function () {
    $processor = app(DxtradeWebsocketEventProcessorContract::class);

    expect($processor)->toBeInstanceOf(DefaultDxtradeWebsocketEventProcessor::class);
});

it('loads the api config', function () {
    expect(config('dxtrade-websocket-api'))->toBeArray();
    expect(config('dxtrade-websocket-api.default'))->toBeArray();
});

it('loads the processor config', function () {
    expect(config('dxtrade-websocket-processor'))->toBeArray();
    expect(config('dxtrade-websocket-processor.processor'))->toBeNull();
});
