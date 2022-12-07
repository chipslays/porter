<?php

namespace Porter;

use Workerman\Connection\TcpConnection;

class Connections
{
    /**
     * Array of connections
     *
     * @var Connection[]
     */
    protected array $connections = [];

    /**
     * Constructor.
     *
     * @param Connection[]|TcpConnection[] $connections
     */
    public function __construct(array $connections = [])
    {
        /** @var Connection|TcpConnection $connection */
        foreach ($connections as $connection) {
            $this->connections[$connection->id] = $connection instanceof TcpConnection
                ? new Connection($connection)
                : $connection;
        }
    }

    /**
     * Send events to all connetions in this collection.
     *
     * @param string $event
     * @param array $data
     * @param array $excepts
     * @return self
     */
    public function broadcast(string $event, array $data = [], array|TcpConnection|Connection $excepts = []): self
    {
        foreach ((array) $excepts as &$value) {
            if ($value instanceof TcpConnection || $value instanceof Connection) {
                $value = $value->id;
            }
        }

        $targets = $this->filter(fn (Connection $connection) => !in_array($connection->id, $excepts));

        Server::getInstance()->to($targets, $event, $data);

        return $this;
    }

    /**
     * Get connections count.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->connections);
    }

    /**
     * Get all connections as array.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->connections;
    }

    /**
     * Get connections id.
     *
     * @return array
     */
    public function ids(): array
    {
        return array_keys($this->connections);
    }

    /**
     * Check connection exists in collection.
     *
     * @param integer $id
     * @return bool
     */
    public function has(int $id): bool
    {
        return array_key_exists($id, $this->connections);
    }

    /**
     * Get specific connections.
     *
     * @param array|integer $ids
     * @return static
     */
    public function only(array|int $ids): static
    {
        $connections = [];

        foreach ((array) $ids as $id) {
            if (isset($this->connections[$id])) {
                $connections[$id] = $this->connections[$id];
            }
        }

        return new static($connections);
    }

    /**
     * Add connection to collection.
     *
     * @param TcpConnection|Connection $connection
     * @return self
     */
    public function add(TcpConnection|Connection $connection): self
    {
        $this->connections[$connection->id] = $connection;

        return $this;
    }

    /**
     * Remove connection from collection.
     *
     * @param TcpConnection|Connection|integer $connection
     * @return self
     */
    public function remove(TcpConnection|Connection|int $connection): self
    {
        $id = !is_int($connection) ? $connection->id : $connection;

        unset($this->connections[$id]);

        return $this;
    }

    /**
     * Get first connection from connection.
     *
     * @return Connection|null
     */
    public function first(): ?Connection
    {
        return array_values($this->connections)[0] ?? null;
    }

    /**
     * Get last connection from connection.
     *
     * @return Connection|null
     */
    public function last(): ?Connection
    {
        return $this->count() > 0 ? end($this->connections) : null;
    }

    /**
     * @param integer $count
     * @param integer $offset
     * @return static
     */
    public function limit(int $count, int $offset = 0): static
    {
        return new static(array_chunk($this->connections, $count, true)[$offset] ?? []);
    }

    /**
     * @param callable|null $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        return new static(array_filter($this->connections, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): static
    {
        $ids = $this->ids();

        $connections = array_map($callback, $this->connections, $ids);

        return new static(array_combine($ids, $connections));
    }

    /**
     * @param callable $callback
     * @return self
     */
    public function each(callable $callback): self
    {
        foreach ($this->connections as $key => $connection) {
            if (call_user_func($callback, $connection, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * @return static
     */
    public function clear(): static
    {
        return new static;
    }

    /**
     * @return Connection
     */
    public function shift(): Connection
    {
        return array_shift($this->connections);
    }

    /**
     * @param integer $size
     * @return array
     */
    public function split(int $size): array
    {
        if ($size <= 0) {
            return new static;
        }

        $chunks = [];

        foreach (array_chunk($this->connections, $size, true) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return $chunks;
    }

    /**
     * @param callable $callback
     * @return self
     */
    public function tap(callable $callback): self
    {
        call_user_func($callback, $this);

        return $this;
    }

    /**
     * @param callable $callback
     * @return mixed
     */
    public function pipe(callable $callback): mixed
    {
        return call_user_func($callback, $this);
    }

    /**
     * Get random connection.
     *
     * @return Connection
     */
    public function random(): Connection
    {
        return $this->connections[array_rand($this->connections)];
    }
}