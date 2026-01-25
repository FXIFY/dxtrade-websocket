<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Concerns;

use Fxify\DxtradeWebsocket\Clients\DxtradeWebsocketClient;

trait DxtradeWebsocketPrintsClientStatus
{
    protected function printClientStatus(DxtradeWebsocketClient $client): void
    {
        $this->comment($client->getClientStatusSummary());
    }
}
