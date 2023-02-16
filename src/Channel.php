<?php

namespace Porter;

use Porter\Traits\Payloadable;
use Porter\Support\Collection;
use Workerman\Connection\TcpConnection;
use Closure;

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
     * @param TcpConnection|Connection|Connections|array $connections
     * @return self
     */
    public function join(TcpConnection|Connection|Connections|array $connections): self
    {
        if ($connections instanceof Connections) {
            $connections = $connections->all();
        }

        $connections = is_array($connections) ? $connections : [$connections];

        foreach ($connections as $connection) {
            $this->connections->add($connection);
            $connection->channels->attach($this->id);

            if ($this->onJoinHandler) {
                call_user_func_array($this->onJoinHandler, [
                    $connection instanceof TcpConnection ? new Connection($connection) : $connection,
                    $this
                ]);
            }
        }

        return $this;
    }

    /**
     * Delete given connection from channel.
     *
     * @param TcpConnection|Connection|Connections|array $connection
     * @return self
     */
    public function leave(TcpConnection|Connection|Connections|array $connections): self
    {
        if ($connections instanceof Connections) {
            $connections = $connections->all();
        }

        $connections = is_array($connections) ? $connections : [$connections];

        foreach ($connections as $connection) {
            if (!$this->exists($connection)) {
                continue;
            };

            $this->connections->remove($connection);
            $connection->channels->detach($this->id);

            if ($this->onLeaveHandler) {
                call_user_func_array($this->onLeaveHandler, [
                    $connection instanceof TcpConnection ? new Connection($connection) : $connection,
                    $this
                ]);
            }
        }

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
        if ($this->onDestroyHandler) {
            call_user_func_array($this->onDestroyHandler, [$this]);
        }

        Server::getInstance()->channels()->delete($this->id);

        $this->__destruct();
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
     * Fire callback before channel destroy.
     *
     * @param callable $callback
     * @return self
     */
    public function onDestroy(callable $callback): self
    {
        $this->onDestroyHandler = $callback;

        return $this;
    }

    /**
     * Fire on destruct object.
     */
    public function __destruct()
    {
        $this->leave($this->connections);
    }
}