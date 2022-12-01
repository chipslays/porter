<?php

use Porter\Server;
use Porter\Payload;
use Porter\Connection;
use Porter\Events\AbstractEvent;

return new class extends AbstractEvent
{
    public string $type = 'chat message';

    protected array $rules = [
        'message' => ['stringType', ['length', [1, 256]]],
    ];

    public function handle(Connection $connection, Payload $payload, Server $server)
    {
        if (!$this->validate()) return;

        $this->broadcast($payload->type, data: [
            'nickname' => $connection->nickname,
            'message' => $payload->data['message'],
        ]);
    }
};