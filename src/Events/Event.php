<?php

namespace Porter\Events;

use Workerman\Connection\TcpConnection;
use Closure;

class Event
{
    /**
     * @var string
     */
    protected string $id;

    /**
     * @var Closure|null
     */
    protected ?Closure $callback = null;

    /**
     * @var int
     */
    protected int $order = 500;

    /**
     * Get the event ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set the event ID.
     *
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function __invoke(TcpConnection $connection, array $payload): void
    {
        $callback = $this->getCallback();

        if ($callback) {
            call_user_func_array($callback, [$connection, $payload]);
        }
    }

    /**
     * Get the event callback.
     *
     * @return Closure
     */
    public function getCallback(): ?Closure
    {
        return $this->callback;
    }

    /**
     * Set the event callback.
     *
     * @param Closure $callback
     * @return self
     */
    public function setCallback(Closure $callback): self
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * Get the event order.
     *
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * Set the event order.
     *
     * @param int $order
     * @return self
     */
    public function setOrder(int $order): self
    {
        $this->order = $order;

        return $this;
    }
}