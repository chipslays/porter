<?php

use Porter\Server;
use Porter\Payload;
use Porter\Connection;
use Porter\Events\AbstractEvent;

return new class extends AbstractEvent
{
    public static string $type = 'ping';

    protected array $rules = [];

    public function handle(Connection $connection, Payload $payload, Server $server)
    {
        $this->reply('pong');
    }
};