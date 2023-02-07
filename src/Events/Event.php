<?php

namespace Porter\Events;

use Porter\Connection;
use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;

class Event extends AbstractEvent
{
    /**
     * This method here is useless, but needed.
     *
     * @return void
     */
    public function handle(Connection $connection, Payload $payload, Server $server): void
    {
        //
    }
}