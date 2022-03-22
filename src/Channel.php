<?php

namespace Porter;

use Chipslays\Collection\Collection;
use Porter\Traits\Payloadable;
use Workerman\Connection\TcpConnection;

class Channel
{
    use Payloadable;

    /** @var TcpConnection[] */
    public array $connections = [];

    public Collection $data;

    /**
     * Constructor.
     *
     * @param string $id
     * @param array $data
     */
    public function __construct(public string $id, array $data = [])
    {
        $this->data = new Collection($data);
    }

    /**
     * Join given connection to channel.
     *
     * @param TcpConnection $connection
     * @return self
     */
    public function join(TcpConnection $connection): self
    {
        $this->connections[$connection->id] = $connection;
        $connection->channels->add($this->id);
        return $this;
    }

    /**
     * Delete given connection from channel.
     *
     * @param TcpConnection $connection
     * @return self
     */
    public function leave(TcpConnection $connection): self
    {
        if (!$this->exists($connection)) return $this;

        unset($this->connections[$connection->id]);
        $connection->channels->delete($this->id);

        return $this;
    }

    /**
     * Checks if given connection exists in channel.
     *
     * @param TcpConnection $connection
     * @return boolean
     */
    public function exists(TcpConnection $connection): bool
    {
        return isset($this->connections[$connection->id]);
    }

    /**
     * Send an event to all connection on this channel.
     *
     * @param string $event asdasd
     * @param array $data
     * @param TcpConnection[] $excepts Connection instance or connection id.
     * @return void
     */
    public function broadcast(string $event, array $data = [], array $excepts = []): void
    {
        foreach ($excepts as &$value) {
            if ($value instanceof TcpConnection) {
                $value = $value->id;
            }
        }

        foreach ($this->connections as $connection) {
            if (in_array($connection->id, $excepts)) continue;
            $connection->send($this->makePayload($event, $data));
        }
    }
}