<?php

use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class SayHello extends AbstractEvent
{
    public static string $id = 'say hello';

    public function handle(TcpConnection $connection, Payload $payload, Server $server): void
    {
        $this->reply('hello username', [
            'message' => 'Hello ' . $this->payload->data['username'] . '!',
        ]);
    }
}
