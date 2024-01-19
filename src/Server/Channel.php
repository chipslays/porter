<?php

namespace Porter\Server;

use Porter\Support\Store;
use Closure;

class Channel
{
    protected Connections $connections;

    protected Store $store;

    /**
     * @var Closure|null
     */
    protected ?Closure $onJoinHandler = null;

    /**
     * @var Closure|null
     */
    protected ?Closure $onLeaveHandler = null;

    /**
     * @var Closure|null
     */
    protected ?Closure $onDestroyHandler = null;

    public function __construct(protected $id)
    {
        $this->connections = new Connections;
        $this->store = new Store;
    }

    /**
     * Returns a channel ID.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Returns a channel store.
     *
     * @return Store
     */
    public function store(): Store
    {
        return $this->store;
    }

    /**
     * Join given connections to channel.
     *
     * @param Connection|Connection[]|Connections|array $connections
     * @return self
     */
    public function join(Connection|Connections|array $connections): self
    {
        if ($connections instanceof Connections) {
            $connections = $connections->all();
        }

        $connections = is_array($connections) ? $connections : [$connections];

        /** @var Connection $connection */
        foreach ($connections as $connection) {
            $this->connections->add($connection);

            $connection->channels()->add($this);

            if ($this->onJoinHandler) {
                call_user_func_array($this->onJoinHandler, [$connection]);
            }
        }

        return $this;
    }

     /**
     * Delete given connection from channel.
     *
     * @param Connection|Connection[]|Connections|array $connection
     * @return self
     */
    public function leave(Connection|Connections|array $connections): self
    {
        if ($connections instanceof Connections) {
            $connections = $connections->all();
        }

        $connections = is_array($connections) ? $connections : [$connections];

        /** @var Connection $connection */
        foreach ($connections as $connection) {
            if (!$this->exists($connection)) {
                continue;
            };

            if ($this->onLeaveHandler) {
                call_user_func_array($this->onLeaveHandler, [$connection]);
            }

            $connection->channels()->remove($this);

            $this->connections->remove($connection);
        }

        return $this;
    }

    /**
     * Checks if given connection exists in channel.
     *
     * @param Connection|int $connection
     * @return bool
     */
    public function exists(Connection|int $connection): bool
    {
        return $this->connections->has($connection);
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

    /**
     * Fire callback after any connection joining to channel.
     *
     * @param callable $callback
     * @return self
     */
    public function onJoin(callable $callback): self
    {
        $this->onJoinHandler = $callback;

        return $this;
    }

    /**
     * Fire callback after any connection leaving channel.
     *
     * @param callable $callback
     * @return self
     */
    public function onLeave(callable $callback): self
    {
        $this->onLeaveHandler = $callback;

        return $this;
    }

    /**
     * Fire on destruct object.
     */
    public function __destruct()
    {
        if ($this->connections->count() === 0) {
            return;
        }

        $this->leave($this->connections);
    }
}