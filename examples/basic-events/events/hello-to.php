<?php

use Porter\Server;
use Porter\Payload;
use Porter\Connection;
use Porter\Events\AbstractEvent;

return new class extends AbstractEvent
{
    public string $type = 'hello to';

    protected array $rules = [
        'username' => ['stringType', ['length', [4, 18]]],
    ];

    protected array $messages = [
        'username' => [
            'stringType' => 'Property %prop% must be a string.',
            'length' => 'Length of %prop% must be 4-18 chars.',
        ],
    ];

    public function handle(Connection $connection, Payload $payload, Server $server)
    {
        if (!$this->validate()) {
            return $this->reply('errors', $this->errorBag()->all());
        };

        $username = $this->payload->data['username'];
        $this->reply(data: ['message' => "Hello, {$username}!"]);
    }
};