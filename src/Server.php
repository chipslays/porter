<?php

namespace Porter;

use Exception;
use Porter\Connection\Channels as ConnectionChannels;
use Porter\Events\Event;
use Workerman\Worker;
use Sauce\Traits\Singleton;
use Workerman\Connection\TcpConnection;

class Server
{
    use Singleton;

    protected Worker $worker;

    public Channels $channels;

    /** @var string[] */
    protected array $events = [];

    /**
     * Set worker.
     *
     * @param Worker $worker
     * @return void
     */
    public function setWorker(Worker $worker):void
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
     * @return void
     */
    protected function bootServer(): void
    {
        $this->channels = new Channels($this);
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
        $this->getWorker()->onWorkerStart = $handler;
    }

    /**
     * Emitted when worker processes stoped.
     *
     * @param callable $handler
     * @return void
     */
    public function onStop(callable $handler): void
    {
        $this->getWorker()->onWorkerStop = $handler;
    }

    /**
     * Emitted when worker processes get reload signal.
     *
     * @param callable $handler
     * @return void
     */
    public function onReload(callable $handler): void
    {
        $this->getWorker()->onWorkerReload = $handler;
    }

    /**
     * Add event.
     *
     * @param string $event
     * @return self
     */
    public function addEvent(string $event): self
    {
        if (isset($this->events[$event::$id])) {
            throw new Exception("Event '{$event::$id}' already exists.");
        }

        $this->events[$event::$id] = $event;
        return $this;
    }

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
    public function start()
    {
        $this->bootServer();

        $this->getWorker()->onMessage = function (TcpConnection $connection, string $payload) {
            $payload = new Payload(json_decode($payload, true));

            $event = $this->events[$payload->eventId] ?? null;

            if (!$event) return;

            if (is_callable($event)) {

                $handler = $event;
                $event = new Event($connection, $payload);
                $event->setHandler($handler);
                $event->altHandle($event);
                return;
            }

            $event = new $event($connection, $payload);
            call_user_func_array([$event, 'handle'], [$connection, $payload, self::getInstance()]);
        };

        $this->getWorker()->runAll();
    }
}