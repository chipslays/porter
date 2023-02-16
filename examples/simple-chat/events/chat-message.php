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

    protected array $messages = [
        'message' => [
            'stringType' => 'Property %prop% must be a string.',
            'length' => 'Length of %prop% must be 1-256 chars.',
        ],
    ];

    public function handle(Connection $connection, Payload $payload, Server $server)
    {
        if (!$this->validate()) {
            return $this->reply('errors', [$this->errorBag()->first('message')]);
        };

        $this->broadcast($payload->type, data: [
            'nickname' => $connection->nickname,
            'message' => $payload->data['message'],
        ]);
    }
};