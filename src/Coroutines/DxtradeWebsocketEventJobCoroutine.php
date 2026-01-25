<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Coroutines;

use Fxify\DxtradeWebsocket\Clients\DxtradeWebsocketClient;
use Fxify\DxtradeWebsocket\Concerns\DxtradeWebsocketProvidesCommandOutput;
use Fxify\DxtradeWebsocket\Contracts\Processors\DxtradeWebsocketEventProcessorContract;
use Fxify\DxtradeWebsocket\Data\DxtradeWebsocketEventData;
use Throwable;

class DxtradeWebsocketEventJobCoroutine
{
    use DxtradeWebsocketProvidesCommandOutput;

    public function __construct(
        private DxtradeWebsocketEventProcessorContract $processor,
    ) {}

    public function handle(DxtradeWebsocketEventData $eventData, DxtradeWebsocketClient $client): void
    {
        try {
            $this->info("Processing {$eventData->type->getLabel()} event");

            // Process the event through the configured processor
            $this->processor->process($eventData->toArray());

        } catch (Throwable $e) {
            $this->report($e);
            $this->error("Error dispatching event job: {$e->getMessage()}");
        }
    }
}
