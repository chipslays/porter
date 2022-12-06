<?php

namespace Porter;

use Porter\Events\Event;
use Porter\Events\AbstractEvent;
use Porter\Traits\Rawable;
use Porter\Traits\Payloadable;
use Porter\Exceptions\PorterException;
use Porter\Connections;
use Sauce\Traits\Singleton;
use Sauce\Traits\Mappable;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Respect\Validation\Validator;

class Server
{
    use Rawable;
    use Mappable;
    use Singleton;
    use Payloadable;

    protected Worker $worker;

    public readonly Channels $channels;

    public readonly Storage $storage;

    public readonly Validator $validator;

    /** @var string[] */
    protected array $events = [];

    /**
     * Set worker.
     *
     * @param Worker $worker
     * @return void
     */
    public function setWorker(Worker $worker): void
    {
        $this->worker = $worker;
        $this->worker->count = 1; // use only 1 worker process

        // set default name for worker
        if (!$this->worker->name || $this->worker->name == 'none') {
            $this->worker->name = 'Server-' . date('d_m_Y-H_i_s');
        }
    }

    /**
     * Get worker instance.
     *
     * @return Worker
     */
    public function getWorker(): Worker
    {
        return $this->worker;
    }

    /**
     * Booting websocket server (use this method instead of constructor).
     *
     * @return self
     */
    public function boot(Worker $worker): self
    {
        $this->setWorker($worker);

        $this->storage = new Storage;
        $this->channels = new Channels($this);
        $this->validator = new Validator;

        return $this;
    }

    /**
     * Emitted when a socket connection is successfully established.
     *
     * @param callable $handler
     * @return self
     */
    public function onConnected(callable $handler): self
    {
        $this->getWorker()->onWebSocketConnect = function (TcpConnection $connection, string $header) use ($handler) {
            call_user_func_array($handler, [new Connection($connection), $header]);
        };

        return $this;
    }

    /**
     * Emitted when the other end of the socket sends a FIN packet.
     *
     * @param callable $handler
     * @return self
     */
    public function onDisconnected(callable $handler): self
    {
        $this->getWorker()->onClose = function (TcpConnection $connection) use ($handler) {
            $connection->channels->leaveAll();
            call_user_func_array($handler, [new Connection($connection)]);
        };

        return $this;
    }

    /**
     * Emitted when an error occurs with connection.
     *
     * @param callable $handler
     * @return self
     */
    public function onError(callable $handler): self
    {
        $this->getWorker()->onError = function (TcpConnection $connection, $code, $message) use ($handler) {
            call_user_func_array($handler, [new Connection($connection), $code, $message]);
        };

        return $this;
    }

    /**
     * Emitted when worker processes start.
     *
     * @param callable $handler
     * @return self
     */
    public function onStart(callable $handler): self
    {
        $this->getWorker()->onWorkerStart = function (Worker $worker) use ($handler) {
            call_user_func_array($handler, [$worker]);
        };

        return $this;
    }

    /**
     * Emitted when worker processes stoped.
     *
     * @param callable $handler
     * @return self
     */
    public function onStop(callable $handler): self
    {
        $this->getWorker()->onWorkerStop = function (Worker $worker) use ($handler) {
            call_user_func_array($handler, [$worker]);
        };

        return $this;
    }

    /**
     * Emitted when worker processes get reload signal.
     *
     * @param callable $handler
     * @return self
     */
    public function onReload(callable $handler): self
    {
        $this->getWorker()->onReload = function (Worker $worker) use ($handler) {
            call_user_func_array($handler, [$worker]);
        };

        return $this;
    }

    /**
     * Add event handler.
     *
     * @param AbstractEvent|string $eventClass
     * @return self
     */
    public function addEvent(AbstractEvent|string $eventClass): self
    {
        $eventClass = $eventClass instanceof AbstractEvent ? $eventClass : new $eventClass;

        if (isset($this->events[$eventClass->type])) {
            throw new PorterException("Event class '{$eventClass->type}' already exists.");
        }

        $this->events[$eventClass->type] = $eventClass;

        return $this;
    }

    /**
     * Autoload event classes.
     *
     * @param string $path
     * @param string|string[] $mask
     * @return self
     *
     * @throws PorterException
     */
    public function autoloadEvents(string $path, string|array $masks = ['*.php', '**/*.php']): self
    {
        $files = array_map(function ($mask) use ($path) {
            return glob(rtrim($path, '/\\') . '/' . ltrim($mask, '/\\'));
        }, $masks);

        foreach (call_user_func('array_merge', ...$files) as $file) {
            $eventClass = require $file;

            if (!$eventClass instanceof AbstractEvent) {
                throw new PorterException('Event must return anonymous class which extends AbstractEvent class: ' . $file);
            }

            $this->addEvent($eventClass);
        }

        return $this;
    }

    /**
     * Event handler as callable.
     *
     * @param string $type
     * @param callable $handler
     * @return self
     */
    public function on(string $type, callable $handler): self
    {
        if (isset($this->events[$type])) {
            throw new PorterException("Event '{$type}' already exists.");
        }

        $this->events[$type] = $handler;

        return $this;
    }

    /**
     * Start server.
     *
     * @param callable $callback Execute on every incoming message.
     * @return void
     */
    public function start(callable $callback = null): void
    {
        $this->getWorker()->onMessage = function (TcpConnection $connection, string $payload) use ($callback) {
            $connection = new Connection($connection);

            // handle heartbeat implementation from client (ping & pong)
            if ($payload == 'ping') {
                $connection->send('pong');
                return;
            }

            // execute callback if exists
            if ($callback) {
                call_user_func_array($callback, [$connection, $payload]);
            }

            $payloadData = @json_decode($payload, true);

            if (!$payloadData || !isset($payloadData['type'])) {
                // handle raw event (no json or no porter event)
                if ($this->onRawHandler) {
                    call_user_func_array($this->onRawHandler, [$payload, $connection]);
                }

                return;
            }

            // handle porter event
            $eventClass = $this->events[$payloadData['type']] ?? null;

            if (!$eventClass) {
                // if client pass wrong event type, skip. do not throw exception!
                return;
            }

            $payload = new Payload($payloadData);

            // if handler anonymous function
            if (is_callable($eventClass)) {
                $handler = $eventClass;

                /** @var Event */
                $eventClass = (new Event)->boot($connection, $payload);
                $eventClass->setHandler($handler);
                $eventClass->altHandle($eventClass);

                return;
            }

            // if handler as event class
            $eventClass = (new $eventClass)->boot($connection, $payload);
            call_user_func_array([$eventClass, 'handle'], [$connection, $payload, self::getInstance()]);
        };

        $this->getWorker()->runAll();
    }

    /**
     * Send event to connection.
     *
     * @param TcpConnection|Connection|Connections|array $connection
     * @param string $event
     * @param array $data
     * @return self
     */
    public function to(TcpConnection|Connection|Connections|array $connection, string $event, array $data = []): self
    {
        if ($connection instanceof Connections) {
            $connection = $connection->all();
        }

        if (!is_array($connection)) {
            $connection = [$connection];
        }

        foreach ($connection as $target) {
            $target->send($this->makePayload($event, $data));
        }

        return $this;
    }

    /**
     * Send event to all connections.
     *
     * @param string $event
     * @param array $data
     * @param int[]|TcpConnection[]|Connection[] $excepts TcpConnection, Connection instance or connection id (ids and instances can as array).
     * @return self
     */
    public function broadcast(string $event, array $data = [], array|TcpConnection|Connection $excepts = []): self
    {
        foreach ((array) $excepts as &$value) {
            if ($value instanceof TcpConnection || $value instanceof Connection) {
                $value = $value->id;
            }
        }

        $targets = $this
            ->connections()
            ->filter(fn (Connection $connection) => !in_array($connection->id, $excepts));

        $this->to($targets, $event, $data);

        return $this;
    }

    /**
     * Getter for Storage class.
     *
     * @return Storage
     */
    public function storage(): Storage
    {
        return $this->storage;
    }

    /**
     * Getter for Channels class.
     *
     * @return Channels
     */
    public function channels(): Channels
    {
        return $this->channels;
    }

    /**
     * Get channel.
     *
     * @param string $id
     * @return Channel|null
     */
    public function channel(string $id): ?Channel
    {
        return $this->channels()->get($id);
    }

    /**
     * Get connection instance by id.
     *
     * @param int $id
     * @return Connection|null
     */
    public function connection(int $id): ?Connection
    {
        if (isset($this->getWorker()->connections[$id])) {
            return new Connection($this->getWorker()->connections[$id]);
        }

        return null;
    }

    /**
     * Get all connections on server.
     *
     * @return Connections
     */
    public function connections(): Connections
    {
        $connections = [];

        foreach ($this->getWorker()->connections as $connection) {
            $connections[$connection->id] = new Connection($connection);
        }

        return new Connections($connections);
    }

    /**
     * Create validator instance.
     *
     * @return Validator
     */
    public function validator(): Validator
    {
        return $this->validator::create();
    }
}