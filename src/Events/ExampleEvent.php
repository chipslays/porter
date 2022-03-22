<?php

use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class ExampleEvent extends AbstractEvent
{
    /**
     * Event id.
     *
     * @var string
     */
    public static string $id = 'ping';

    /**
     * Handle incoming event from client.
     *
     * @return void
     */
    public function handle(TcpConnection $connection, Payload $payload, Server $server): void
    {
        $this->reply('pong');
    }
}