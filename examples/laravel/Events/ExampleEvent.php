<?php

use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class ExampleEvent extends AbstractEvent
{
    public static string $type = 'ping';

    protected array $rules = [];

    public function handle(TcpConnection $connection, Payload $payload, Server $server)
    {
        $this->reply('pong');
    }
}

server()->addEvent(ExampleEvent::class);