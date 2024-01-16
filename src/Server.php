<?php

namespace Porter;

use Porter\Events\Event;
use Porter\Events\Bus as EventBus;
use Porter\Server\Connection;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Closure;
use Porter\Events\Payload;
use Porter\Server\Connections;

class Server
{
    /**
     * @var Worker
     */
    protected Worker $worker;

    /**
     * @var EventBus
     */
    protected EventBus $eventBus;

    /**
     * @var Closure|null
     */
    protected ?Closure $messageCallback = null;

    /**
     * Constructor.
     *
     * @param string $address A valid socket address like 0.0.0.0:8080
     * @param array $context https://www.php.net/manual/ru/context.socket.php
     * @param int $processes https://www.workerman.net/doc/workerman/faq/processes-count.html
     */
    public function __construct(string $ip, int $port, array $context = [], int $processes = 1)
    {
        $this->createWorkerInstance(...func_get_args());
        $this->registerEventAndMessageCallbacks();

        $this->eventBus = new EventBus;
    }

    protected function createWorkerInstance(string $ip, int $port, array $context = [], int $processes = 1): void
    {
        $this->worker = new Worker('websocket://' . $ip . ':' . $port, $context);
        $this->worker->count = $processes;
        $this->worker->name = 'Server-' . date('d-m-Y_H-i-s');
    }

    /**
     * Get a worker instance.
     *
     * @return Worker
     */
    public function getWorker(): Worker
    {
        return $this->worker;
    }

    /**
     * Override a new worker instance.
     *
     * @param Worker $worker
     * @return self
     */
    public function setWorker(Worker $worker): self
    {
        $this->worker = $worker;

        return $this;
    }

    /**
     * Gets a event bus.
     *
     * @return EventBus
     */
    public function getEventBus(): EventBus
    {
        return $this->eventBus;
    }

    /**
     * The callback function triggered when the client establishes
     * a connection with Workerman (after the TCP three-way handshake is completed).
     *
     * The callback will only be triggered once per connection.
     *
     * Event only represents that the client and Workerman have completed
     * the TCP three-way handshake.
     *
     * At this time, the client has not sent any data.
     *
     * @see https://www.workerman.net/doc/workerman/worker/on-connect.html
     *
     * @param Closure $callback
     * @return self
     */
    public function onConnect(Closure $callback): self
    {
        $this->worker->onConnect = function (TcpConnection $connection) use ($callback) {
            call_user_func_array($callback, [new Connection($connection)]);
        };

        return $this;
    }

    /**
     * The callback function triggered when the client connects to
     * the gateway and completes the websocket handshake.
     *
     * You can get the http header of websocket handshake by $_SERVER in it.
     *
     * @param Closure $callback
     * @return self
     */
    public function onConnected(Closure $callback): self
    {
        $this->worker->onWebSocketConnect = function (TcpConnection $connection, string $header) use ($callback) {
            call_user_func_array($callback, [new Connection($connection), $header]);
        };

        return $this;
    }

    /**
     * The callback function triggered when the client connection is disconnected from the server.
     *
     * This will only be triggered once per connection.
     *
     * @see https://www.workerman.net/doc/workerman/worker/on-close.html
     *
     * @param Closure $callback
     * @return self
     */
    public function onDisconnected(Closure $callback): self
    {
        $this->worker->onClose = function (TcpConnection $connection) use ($callback) {
            call_user_func_array($callback, [new Connection($connection)]);
        };

        return $this;
    }

    /**
     * Fired when an error occurs on the client's connection.
     *
     * @see https://www.workerman.net/doc/workerman/worker/on-error.html
     *
     * @param Closure $callback
     * @return self
     */
    public function onError(Closure $callback): self
    {
        $this->worker->onError = function (TcpConnection $connection, int $code, string $message) use ($callback) {
            call_user_func_array($callback, [new Connection($connection), $code, $message]);
        };

        return $this;
    }

    /**
     * Emitted when a Worker process start.
     *
     * @see https://www.workerman.net/doc/workerman/worker/on-worker-start.html
     *
     * @param Closure $callback
     * @return self
     */
    public function onStart(Closure $callback): self
    {
        $this->worker->onWorkerStart = function (Worker $worker) use ($callback) {
            call_user_func_array($callback, [$worker]);
        };

        return $this;
    }

    /**
     * Emitted when a Woker process stop.
     *
     * @param Closure $callback
     * @return self
     */
    public function onStop(Closure $callback): self
    {
        $this->worker->onWorkerStart = function (Worker $worker) use ($callback) {
            call_user_func_array($callback, [$worker]);
        };

        return $this;
    }

    /**
     * Set the callback executed after the Worker receives the reload signal.
     *
     * You can use callback to do many things, such as reloading the business configuration file without restarting the process.
     *
     * This feature is not commonly used.
     *
     * @see https://www.workerman.net/doc/workerman/worker/on-worker-reload.html
     *
     * @param Closure $callback
     * @return self
     */
    public function onReload(Closure $callback): self
    {
        $this->worker->onWorkerStart = function (Worker $worker) use ($callback) {
            call_user_func_array($callback, [$worker]);
        };

        return $this;
    }

    /**
     * Each connection has a separate application layer sending buffer.
     *
     * If the client's receiving speed is slower than the server's sending speed,
     * the data will be temporarily stored in the application layer buffer.
     *
     * If the buffer is full, the `onBufferFull` callback will be triggered.
     *
     * The buffer size is `$connection::$maxSendBufferSize`, default value is 1MB.
     *
     * The buffer size can be dynamically set for the current connection.
     *
     * @see https://www.workerman.net/doc/workerman/worker/on-buffer-full.html
     *
     * @param Closure $callback
     * @return self
     */
    public function onBufferFull(Closure $callback): self
    {
        $this->worker->onBufferFull = function (TcpConnection $connection) use ($callback) {
            call_user_func_array($callback, [new Connection($connection)]);
        };

        return $this;
    }

    /**
     * Each connection has a separate application layer send buffer.
     *
     * The buffer size is `Workerman\Connection\TcpConnection::$maxSendBufferSize` determined by.
     *
     * The default value is 1MB.
     *
     * You can manually set and change the size, **the change will take effect on all connections.**
     *
     * @see https://www.workerman.net/doc/workerman/worker/on-buffer-drain.html
     *
     * @param Closure $callback
     * @return self
     */
    public function onBufferDrain(Closure $callback): self
    {
        $this->worker->onBufferDrain = function (TcpConnection $connection) use ($callback) {
            call_user_func_array($callback, [new Connection($connection)]);
        };

        return $this;
    }

    /**
     * The callback function triggered when the client sends
     * raw data (not valid `Event`) through the connection.
     *
     * @see https://www.workerman.net/doc/workerman/worker/on-message.html
     *
     * @param Closure $callback
     * @return self
     */
    public function onMessage(Closure $callback): self
    {
        $this->messageCallback = function (Connection $connection, mixed $data) use ($callback) {
            call_user_func_array($callback, [$connection, $data]);
        };

        return $this;
    }

    /**
     * Add the custom event (message).
     *
     * @param string $id
     * @param Closure $callback
     * @param integer $order
     * @return self
     */
    public function on(string $id, Closure $callback, int $order = 500): Event
    {
        $event = (new Event)
            ->setId($id)
            ->setCallback($callback)
            ->setOrder($order);

        $this->eventBus->add($event);

        return $event;
    }

    /**
     * Start the server.
     *
     * @return void
     */
    public function start(): void
    {
        if (extension_loaded('pcntl')) {
            $this->worker->run();
        } else {
            $this->worker->runAll();
        }
    }

    /**
     * @return void
     */
    protected function registerEventAndMessageCallbacks(): void
    {
        $this->worker->onMessage = function (TcpConnection $connection, string $data) {
            // Try decode incoming data.
            $event = json_decode($data, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Handle as Event.
                $this->handleEvent(new Connection($connection), (array) $event, $data);
            } else {
                // Handle as raw message.
                $this->handleMessage(new Connection($connection), $data);
            }
        };
    }

    /**
     * @param Connection $connection
     * @param array $payload
     * @param string $data
     * @return void
     */
    protected function handleEvent(Connection $connection, array $event, string $data): void
    {
        // If it not valid a event ID.
        if (empty($event['id']) || trim($event['id']) === '') {
            // Try handle as raw message data.
            $this->handleMessage($connection, $data);

            return;
        }

        // Find event by ID.
        $eventInstance = $this->eventBus->find($event['id']);

        // If event not found.
        if (!$eventInstance) {
            return;
        }

        // Trigger event callback.
        $eventInstance($connection, new Payload((array) @$event['data']));
    }

    /**
     * @param Connection $connection
     * @param string $data
     * @return void
     */
    protected function handleMessage(Connection $connection, string $data): void
    {
        // If message callback not set.
        if (!$this->messageCallback) {
            return;
        }

        // Trigger message callback.
        call_user_func_array($this->messageCallback, [$connection, $data]);
    }

    /**
     * Returns collection of connections.
     *
     * @return Connections
     */
    public function getConnections(): Connections
    {
        return new Connections($this->worker->connections);
    }

    /**
     * Send events to all connetions in this collection.
     *
     * @param string $id
     * @param array|Closure|Payload $data
     * @param TcpConnection|Connection|TcpConnection[]|Connection[]|array $excepts
     * @return void
     */
    public function broadcast(string $id, array|Closure|Payload $data = [], TcpConnection|Connection|array $excepts = []): void
    {
        $this->getConnections()->broadcast($id, $data, $excepts);
    }
}