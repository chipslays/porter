<?php

use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class HelloToEvent extends AbstractEvent
{
    public static string $eventId = 'hello to';

    public function handle(TcpConnection $connection, Payload $payload, Server $server): void
    {
        $username = $this->payload->data['username'];
        $this->reply(data: ['message' => "Hello, {$username}!"]);
    }
}
