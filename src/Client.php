<?php

namespace Porter;

use Porter\Events\Event;
use Porter\Events\Payload;
use Porter\Events\Bus as EventBus;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;
use Closure;

class Client
{
    /**
     * Current connection instance.
     *
     * @var AsyncTcpConnection
     */
    public AsyncTcpConnection $connection;

    /**
     * Worker instance.
     *
     * @var Worker
     */
    public Worker $worker;

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
     * @param string $host
     * @param array $context
     */
    public function __construct(string $host, array $context = [])
    {
        $this->createWorkerInstance();

        $this->createConnectionInstance(...func_get_args());

        $this->eventBus = new EventBus;
    }

    protected function createWorkerInstance(): void
    {
        $this->worker = new Worker;
        $this->worker->count = 1; // use only 1 worker process
        $this->worker->name = 'Client-' . date('d-m-Y_H-i-s');
    }

    protected function createConnectionInstance(string $host, array $context = []): void
    {
        $this->connection = new AsyncTcpConnection($host, $context);
    }

    /**
     * Set worker.
     *
     * @param Worker $worker
     * @return void
     */
    public function setWorker(Worker $worker): void
    {
        $this->worker = $worker;
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
     * Gets a event bus.
     *
     * @return EventBus
     */
    public function events(): EventBus
    {
        return $this->eventBus;
    }

    /**
     * Send event to server.
     *
     * @param string $type
     * @param array $data
     * @return bool|null
     */
    public function event(string $id, array|Closure|Payload $data = []): void
    {
        if ($data instanceof Closure) {
            $data = call_user_func($data);
        }

        if ($data instanceof Payload) {
            $data = $data->all();
        }

        $event = (new Event)
            ->setId($id)
            ->setData($data);

        $this->connection->send((string) $event);
    }

    /**
     * Send raw payload to server.
     *
     * @param string $buffer
     * @param bool $raw
     * @return bool|null
     */
    public function send(string $buffer, bool $raw = false): ?bool
    {
        return $this->connection->send(...func_get_args());
    }

    /**
     * Generally `onDisconnect` called in callback to achieve
     * disconnection and reconnection.
     *
     * If the connection is disconnected due to network problems
     * or restart of the other party's service, you can reconnect
     * by calling this method.
     *
     * @param integer $delay    The unit is seconds, supports decimals, and can be accurate to milliseconds.
     *                          If not passed or the value is 0, it means immediate reconnection.
     * @return void
     */
    public function reconnect(float $delay = 0): void
    {
        $this->connection->reConnect($delay);
    }

    /**
     * Disconnect from server and close connection.
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->connection->close();
    }

    /**
     * Emitted when a socket connection is successfully established.
     *
     * @param callable $handler
     * @return void
     */
    public function onConnect(callable $handler): void
    {
        $this->connection->onConnect = function (AsyncTcpConnection $connection) use ($handler) {
            call_user_func_array($handler, [$connection]);
        };
    }

    /**
     * Emitted when server sends a FIN packet.
     *
     * @param callable $handler
     * @return void
     */
    public function onDisconnect(callable $handler): void
    {
        $this->connection->onClose = function (AsyncTcpConnection $connection) use ($handler) {
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
        $this->connection->onError = function (AsyncTcpConnection $connection, $code, $message) use ($handler) {
            call_user_func_array($handler, [$connection, $code, $message]);
        };
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
        $this->messageCallback = function (AsyncTcpConnection $connection, mixed $data) use ($callback) {
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
    public function listen(): void
    {
        $this->registerEventAndMessageCallbacks();

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
        $this->worker->onWorkerStart = function () {
            $this->connection->onMessage = function (AsyncTcpConnection $connection, string $data) {
                // Try decode incoming data.
                $event = json_decode($data, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // Handle as Event.
                    $this->handleEvent($connection, (array) $event, $data);
                } else {
                    // Handle as raw message.
                    $this->handleMessage($connection, $data);
                }
            };

            $this->connection->connect();

        };
    }

    /**
     * @param AsyncTcpConnection $connection
     * @param array $payload
     * @param string $data
     * @return void
     */
    protected function handleEvent(AsyncTcpConnection $connection, array $event, string $data): void
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
    protected function handleMessage(AsyncTcpConnection $connection, string $data): void
    {
        // If message callback not set.
        if (!$this->messageCallback) {
            return;
        }

        // Trigger message callback.
        call_user_func_array($this->messageCallback, [$connection, $data]);
    }
}