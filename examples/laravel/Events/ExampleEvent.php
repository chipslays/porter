<?php

use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class ExampleEvent extends AbstractEvent
{
    public static string $eventId = 'ping';

    public function handle(TcpConnection $connection, Payload $payload, Server $server): void
    {
        $this->reply('pong');
    }
}

server()->addEvent(ExampleEvent::class);