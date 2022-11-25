<?php

namespace Porter;

use Porter\Events\Event;
use Porter\Events\AbstractEvent;
use Porter\Traits\Rawable;
use Porter\Traits\Payloadable;
use Porter\Connection\Channels as ConnectionChannels;
use Porter\Exceptions\PorterException;
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

    public Channels $channels;

    public Storage $storage;

    public Validator $validator;

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

        // set default name for worker
        if (!$this->worker->name || $this->worker->name == 'none') {
            $this->worker->name = 'Porter#' . mt_rand(1000, 9999);
        }

        $this->bootServer();
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
     * Init classes.
     *
     * @return void
     */
    protected function bootServer(): void
    {
        $this->storage = new Storage;
        $this->channels = new Channels($this);
        $this->validator = new Validator;
    }

    /**
     * Attach features to incoming connection.
     *
     * @param TcpConnection $connection
     * @return void
     */
    protected function initConnection(TcpConnection $connection): void
    {
        $connection->channels = new ConnectionChannels($connection);
    }

    /**
     * Emitted when a socket connection is successfully established.
     *
     * @param callable $handler
     * @return void
     */
    public function onConnected(callable $handler): void
    {
        $this->getWorker()->onConnect = function (TcpConnection $connection) {
            // init connection there
            $this->initConnection($connection);
        };

        $this->getWorker()->onWebSocketConnect = function (TcpConnection $connection, string $header) use ($handler) {
            call_user_func_array($handler, [$connection, $header]);
        };
    }

    /**
     * Emitted when the other end of the socket sends a FIN packet.
     *
     * @param callable $handler
     * @return void
     */
    public function onDisconnected(callable $handler): void
    {
        $this->getWorker()->onClose = function (TcpConnection $connection) use ($handler) {
            // fix: ErrorException: Undefined property: Workerman\Connection\TcpConnection::$channels in /srv/naplenke.online/vendor/chipslays/porter/src/Server.php
            if (property_exists($this, 'channels')) {
                $connection->channels->leaveAll();
            }
            call_user_func_array($handler, [$connection]);
        };
    }

    /**
     * Emitted when an error occurs with connection.
     *
     * @param callable $handler
     * @return void
     */
    public function onError(callable $handler): void
    {
        $this->getWorker()->onError = function (TcpConnection $connection, $code, $message) use ($handler) {
            call_user_func_array($handler, [$connection, $code, $message]);
        };
    }

    /**
     * Emitted when worker processes start.
     *
     * @param callable $handler
     * @return void
     */
    public function onStart(callable $handler): void
    {
        $this->getWorker()->onWorkerStart = function (Worker $worker) use ($handler) {
            call_user_func_array($handler, [$worker]);
        };
    }

    /**
     * Emitted when worker processes stoped.
     *
     * @param callable $handler
     * @return void
     */
    public function onStop(callable $handler): void
    {
        $this->getWorker()->onWorkerStop = function (Worker $worker) use ($handler) {
            call_user_func_array($handler, [$worker]);
        };
    }

    /**
     * Emitted when worker processes get reload signal.
     *
     * @param callable $handler
     * @return void
     */
    public function onReload(callable $handler): void
    {
        $this->getWorker()->onReload = function (Worker $worker) use ($handler) {
            call_user_func_array($handler, [$worker]);
        };
    }

    /**
     * Add event handler.
     *
     * @param string $className
     * @return self
     */
    public function addEvent(string $className): self
    {
        if (isset($this->events[$className::$type])) {
            throw new PorterException("Event '{$className::$type}' already exists.");
        }

        $this->events[$className::$type] = $className;

        return $this;
    }

    /**
     * Autoload event classes.
     *
     * @param string $path
     * @param string $mask
     * @return void
     */
    public function autoloadEvents(string $path, string|array $masks = ['*.php', '**/*.php']): void
    {
        $files = array_map(function ($mask) use ($path) {
            return glob(rtrim($path, '/\\') . '/' . ltrim($mask, '/\\'));
        }, $masks);

        foreach (call_user_func('array_merge', ...$files) as $file) {
            $className = require $file;

            if ($className == 1) {
                throw new PorterException("Event class must return class name when loading by 'autoloadEvents' method.");
            }

            $this->addEvent($className);
        }
    }

    /**
     * Event handler as callable.
     *
     * @param string $type
     * @param callable $handler
     * @return void
     */
    public function on(string $type, callable $handler): void
    {
        if (isset($this->events[$type])) {
            throw new PorterException("Event '{$type}' already exists.");
        }

        $this->events[$type] = $handler;
    }

    /**
     * Start server.
     *
     * @return void
     */
    public function start(): void
    {
        $this->getWorker()->onMessage = function (TcpConnection $connection, string $payload) {
            // handle heartbeat implementation (ping & pong)
            if ($payload == 'ping') {
                $connection->send('pong');
                return;
            }

            $payloadData = @json_decode($payload, true);

            if ($payloadData) {
                $payload = new Payload($payloadData);
            } else {
                if ($this->onRawHandler) {
                    call_user_func_array($this->onRawHandler, [$payload, $connection]);
                }
                return;
            }

            $event = $this->events[$payload->type] ?? null;

            if (!$event) {
                return;
            }

            if (is_callable($event)) {
                $handler = $event;
                $event = new Event($connection, $payload);
                $event->setHandler($handler);
                $event->altHandle($event);

                return;
            }

            /** @var AbstractEvent */
            $event = new $event($connection, $payload);
            call_user_func_array([$event, 'handle'], [$connection, $payload, self::getInstance()]);
        };

        $this->getWorker()->runAll();
    }

    /**
     * Send event to connection.
     *
     * @param TcpConnection|Connection $connection
     * @param string $event
     * @param array $data
     * @return bool|null
     */
    public function to(TcpConnection|Connection $connection, string $event, array $data = []): ?bool
    {
        return $connection->send($this->makePayload($event, $data));
    }

    /**
     * Send event to all connections.
     *
     * @param string $event
     * @param array $data
     * @param int[]|TcpConnection[]|Connection[] $excepts TcpConnection, Connection instance or connection ids.
     * @return void
     */
    public function broadcast(string $event, array $data = [], array|TcpConnection|Connection $excepts = []): void
    {
        foreach ((array) $excepts as &$value) {
            if ($value instanceof TcpConnection || $value instanceof Connection) {
                $value = $value->id;
            }
        }

        foreach ($this->getWorker()->connections as $connection) {
            if (in_array($connection->id, $excepts)) {
                continue;
            }

            $this->to($connection, $event, $data);
        }
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
     * Get connection instance by id.
     *
     * @param integer $connectionId
     * @return TcpConnection|null
     */
    public function connection(int $connectionId): ?TcpConnection
    {
        return $this->getWorker()->connections[$connectionId] ?? null;
    }

    /**
     * Get all connections on server.
     *
     * @return TcpConnection[]
     */
    public function connections(): array
    {
        return $this->getWorker()->connections;
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