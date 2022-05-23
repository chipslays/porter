<?php

use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class PingEvent extends AbstractEvent
{
    public static string $type = 'ping';

    protected array $rules = [];

    public function handle(TcpConnection $connection, Payload $payload, Server $server)
    {
        $this->reply('pong');
    }
}