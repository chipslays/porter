<?php

namespace Porter\Events;

use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class Event extends AbstractEvent
{
    protected $handler;

    /**
     * Handle incoming event from client.
     *
     * @return void
     */
    public function handle(TcpConnection $connection, Payload $payload, Server $server): void
    {
        //
    }

    /**
     * @param Event $event
     * @return void
     */
    public function altHandle(Event $event): void
    {
        if (!$this->handler) return;
        call_user_func_array($this->handler, [$event]);
    }

    /**
     * @param callable $handler
     * @return self
     */
    public function setHandler(callable $handler): self
    {
        $this->handler = $handler;

        return $this;
    }
}