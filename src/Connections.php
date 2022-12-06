<?php

namespace Porter;

use Workerman\Connection\TcpConnection;

class Connections
{
    protected array $connections = [];

    public function __construct(array $connections = [])
    {
        /** @var Connection|TcpConnection $connection */
        foreach ($connections as $connection) {
            $this->connections[$connection->id] = $connection instanceof TcpConnection
                ? new Connection($connection)
                : $connection;
        }
    }

    public function broadcast(string $event, array $data = [], array|TcpConnection|Connection $excepts = [])
    {
        foreach ((array) $excepts as &$value) {
            if ($value instanceof TcpConnection || $value instanceof Connection) {
                $value = $value->id;
            }
        }

        $targets = $this->filter(fn (Connection $connection) => !in_array($connection->id, $excepts));

        Server::getInstance()->to($targets, $event, $data);
    }

    public function count(): int
    {
        return count($this->connections);
    }

    public function all(): array
    {
        return $this->connections;
    }

    public function ids(): array
    {
        return array_keys($this->connections);
    }

    public function has(int $id): bool
    {
        return array_key_exists($id, $this->connections);
    }

    public function only($ids): static
    {
        $connections = [];

        foreach ($ids as $id) {
            if (isset($this->connections[$id])) {
                $connections[$id] = $this->connections[$id];
            }
        }

        return new static($connections);
    }

    public function push(TcpConnection|Connection $connection): self
    {
        $this->connections[$connection->id] = $connection;

        return $this;
    }

    public function remove(TcpConnection|Connection|int $connection): self
    {
        $id = !is_int($connection) ? $connection->id : $connection;

        unset($this->connections[$id]);

        return $this;
    }

    public function first(): ?Connection
    {
        return array_values($this->connections)[0] ?? null;
    }

    public function last(): ?Connection
    {
        return $this->count() > 0 ? end($this->connections) : null;
    }

    public function limit(int $count, int $offset): static
    {
        return new static(array_chunk($this->connections, $count, true)[$offset] ?? []);
    }

    public function filter(callable $callback = null): static
    {
        if ($callback) {
            return new static(array_filter($this->connections, $callback, ARRAY_FILTER_USE_BOTH));
        }

        return new static(array_filter($this->connections));
    }

    public function map(callable $callback): static
    {
        $ids = $this->ids();

        $connections = array_map($callback, $this->connections, $ids);

        return new static(array_combine($ids, $connections));
    }

    public function each(callable $callback): self
    {
        foreach ($this->connections as $key => $item) {
            if (call_user_func($callback, $item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    public function clear(): static
    {
        return new static;
    }

    public function shift(): Connection
    {
        return array_shift($this->connections);
    }

    public function split($size): array
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
}