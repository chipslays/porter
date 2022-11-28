<?php

use Porter\Server;
use Porter\Payload;
use Porter\Connection;
use Porter\Events\AbstractEvent;

class HelloToEvent extends AbstractEvent
{
    public static string $type = 'hello to';

    protected array $rules = [
        'username' => ['stringType', ['length', [4, 18]]],
    ];

    public function handle(Connection $connection, Payload $payload, Server $server)
    {
        if ($this->validate()) {
            return $this->reply('bad request', ['errors' => $this->errors]);
        }

        $username = $this->payload->data['username'];
        $this->reply(data: ['message' => "Hello, {$username}!"]);
    }
}

return HelloToEvent::class;