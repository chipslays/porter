<?php

use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class HelloToEvent extends AbstractEvent
{
    public static string $type = 'hello to';

    protected array $rules = [
        'username' => ['stringType', ['length', [4, 18]]],
    ];

    public function handle(TcpConnection $connection, Payload $payload, Server $server)
    {
        if ($this->hasErrors()) {
            return $this->reply('bad request', ['errors' => $this->errors]);
        }

        $username = $this->payload->data['username'];
        $this->reply(data: ['message' => "Hello, {$username}!"]);
    }
}

return HelloToEvent::class;