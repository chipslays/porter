<?php

use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class ChatMessageEvent extends AbstractEvent
{
    public static string $type = 'chat message';

    protected array $rules = [
        'message' => ['stringType', ['length', [1, 256]]],
    ];

    public function handle(TcpConnection $connection, Payload $payload, Server $server)
    {
        if ($this->hasErrors()) return;

        $this->broadcast($payload->type, data: [
            'nickname' => $connection->nickname,
            'message' => $payload->data['message'],
        ]);
    }
}
