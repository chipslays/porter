<?php

use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class HelloToEvent extends AbstractEvent
{
    public static string $eventId = 'hello to';

    protected array $rules = [
        'username' => ['stringType', ['length', [4, 18]]],
    ];

    public function handle(TcpConnection $connection, Payload $payload, Server $server): void
    {
        if ($this->hasErrors()) {
            $this->reply('bad request', ['errors' => $this->errors]);
            return;
        }

        $username = $this->payload->data['username'];
        $this->reply(data: ['message' => "Hello, {$username}!"]);
    }
}