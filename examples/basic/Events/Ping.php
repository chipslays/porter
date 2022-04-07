<?php

use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class Ping extends AbstractEvent
{
    public static string $id = 'ping';

    public function handle(TcpConnection $connection, Payload $payload, Server $server): void
    {
        $this->reply('pong');
    }
}