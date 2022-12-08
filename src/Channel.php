<?php

namespace Porter;

use Porter\Traits\Payloadable;
use Porter\Support\Collection;
use Workerman\Connection\TcpConnection;

class Channel
{
    use Payloadable;

    /**
     * Joined connections to channel.
     *
     * @var Connections
     */
    protected Connections $connections;

    /**
     * Local channel data.
     *
     * @var Collection
     */
    public Collection $data;

    /**
     * Constructor.
     *
     * @param string $id
     * @param array $data
     */
    public function __construct(public string $id, array $data = [])
    {
        $this->connections = new Connections;
        $this->data = new Collection($data);
    }

    /**
     * Join given connections to channel.
     *
     * @param TcpConnection|TcpConnection[]|Connection|Connection[] $connections
     * @return self
     */
    public function join(TcpConnection|Connection|array $connections): self
    {
        $connections = is_array($connections) ? $connections : [$connections];

        foreach ($connections as $connection) {
            $this->connections->add($connection);
            $connection->channels->attach($this->id);
        }

        return $this;
    }

    /**
     * Delete given connection from channel.
     *
     * @param TcpConnection|Connection $connection
     * @return self
     */
    public function leave(TcpConnection|Connection $connection): self
    {
        if (!$this->exists($connection)) {
            return $this;
        };

        $this->connections->remove($connection);
        $connection->channels->detach($this->id);

        return $this;
    }

    /**
     * Checks if given connection exists in channel.
     *
     * @param TcpConnection|Connection|int $connection
     * @return bool
     */
    public function exists(TcpConnection|Connection|int $connection): bool
    {
        return $this->connections->has($connection);
    }

    /**
     * Send an event to all connection on this channel.
     *
     * @param string $event
     * @param array $data
     * @param array|TcpConnection|Connection $excepts Connection instance or connection id.
     * @return self
     */
    public function broadcast(string $event, array $data = [], array|TcpConnection|Connection $excepts = []): self
    {
        $this->connections->broadcast($event, $data, $excepts);

        return $this;
    }

    /**
     * Delete this channel from channels.
     *
     * @return void
     */
    public function destroy(): void
    {
        Server::getInstance()->channels()->delete($this->id);
    }

    /**
     * Get all connections in channel.
     *
     * @return Connections
     */
    public function connections(): Connections
    {
        return $this->connections;
    }
}