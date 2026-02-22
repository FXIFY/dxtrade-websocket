<?php

declare(strict_types=1);

namespace Fxify\DxtradeWebsocket\Support;

use Fxify\DxtradeWebsocket\Data\DxtradeLifecycleEventData;

class DxtradeLifecycle
{
    /**
     * @param array<string, mixed> $event
     */
    public static function from(array $event): ?DxtradeLifecycleEventData
    {
        return DxtradeLifecycleEventParser::parse($event);
    }
}
