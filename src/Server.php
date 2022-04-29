<?php

namespace Porter;

use Porter\Events\Event;
use Porter\Events\AbstractEvent;
use Porter\Traits\Rawable;
use Porter\Traits\Payloadable;
use Porter\Connection\Channels as ConnectionChannels;
use Sauce\Traits\Singleton;
use Sauce\Traits\Mappable;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Respect\Validation\Validator;
use Exception;

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
     * @return void
     */
    protected function bootServer(): void
    {
        $this->storage = new Storage;
        $this->channels = new Channels($this);
        $this->validator = new Validator;
    }

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
        $this->getWorker()->onConnect = function (TcpConnection $connection) use ($handler) {
            $this->initConnection($connection);
            call_user_func_array($handler, [$connection]);
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
            $connection->channels->leaveAll();
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
     * @param string $event
     * @return self
     */
    public function addEvent(string $event): self
    {
        if (isset($this->events[$event::$eventId])) {
            throw new Exception("Event '{$event::$eventId}' already exists.");
        }

        $this->events[$event::$eventId] = $event;

        return $this;
    }

    /**
     * Event handler as callable.
     *
     * @param string $eventId
     * @param callable $handler
     * @return void
     */
    public function on(string $eventId, callable $handler): void
    {
        if (isset($this->events[$eventId])) {
            throw new Exception("Event '{$eventId}' already exists.");
        }

        $this->events[$eventId] = $handler;
    }

    /**
     * Start server.
     *
     * @return void
     */
    public function start(): void
    {
        $this->getWorker()->onMessage = function (TcpConnection $connection, string $payload) {
            $payloadData = @json_decode($payload, true);

            if ($payloadData) {
                $payload = new Payload($payloadData);
            } else {
                if ($this->onRawHandler) {
                    call_user_func_array($this->onRawHandler, [$payload, $connection]);
                }
                return;
            }

            $event = $this->events[$payload->eventId] ?? null;

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
        if ($connection instanceof Connection) {
            $target = $connection->getTcpConnection();
        } else {
            $target = $connection;
        }

        return $target->send($this->makePayload($event, $data));
    }

    /**
     * Send event to all connections.
     *
     * @param string $event
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

        foreach ($this->getWorker()->connections as $connection) {
            if (in_array($connection->id, $excepts)) continue;
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
    public function getConnection(int $connectionId): ?TcpConnection
    {
        return $this->getWorker()->connections[$connectionId] ?? null;
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