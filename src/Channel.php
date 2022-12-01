<?php

namespace Porter;

use Porter\Traits\Payloadable;
use Porter\Support\Collection;
use Workerman\Connection\TcpConnection;

class Channel
{
    use Payloadable;

    /** @var array Of TcpConnection and Connection */
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
     * Join given connections to channel.
     *
     * @param TcpConnection|TcpConnection[]|Connection|Connection[] $connections
     * @return self
     */
    public function join(TcpConnection|Connection|array $connections): self
    {
        $connections = is_array($connections) ?: [$connections];

        foreach ($connections as $connection) {
            $this->connections[$connection->id] = $connection;
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

        unset($this->connections[$connection->id]);
        $connection->channels->detach($this->id);

        return $this;
    }

    /**
     * Checks if given connection exists in channel.
     *
     * @param Connection $connection
     * @return bool
     */
    public function exists(TcpConnection|Connection $connection): bool
    {
        return isset($this->connections[$connection->id]);
    }

    /**
     * Send an event to all connection on this channel.
     *
     * @param string $event
     * @param array $data
     * @param Connection[] $excepts Connection instance or connection id.
     * @return void
     */
    public function broadcast(string $event, array $data = [], TcpConnection|Connection|array $excepts = []): void
    {
        foreach ((array) $excepts as &$value) {
            if ($value instanceof Connection || $value instanceof TcpConnection) {
                $value = $value->id;
            }
        }

        foreach ($this->connections as $connection) {
            if (in_array($connection->id, $excepts)) {
                continue;
            }

            $connection->send($this->makePayload($event, $data));
        }
    }

    /**
     * Delete this channel from channels.
     *
     * @return void
     */
    public function destroy(): void
    {
        Server::getInstance()->channels->delete($this->id);
    }
}