<?php

namespace Porter;

use Porter\Traits\Rawable;
use Porter\Traits\Payloadable;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;
use Exception;

class Client
{
    use Rawable;
    use Payloadable;

    public AsyncTcpConnection $connection;

    public Worker $worker;

    protected array $events = [];

    /**
     * Constructor.
     *
     * @param string $host
     * @param array $context
     */
    public function __construct(string $host, array $context = [])
    {
        $this->worker = new Worker;
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
     * Send event to server.
     *
     * @param string $type
     * @param array $data
     * @return bool|null
     */
    public function event(string $type, array $data = []): ?bool
    {
        return $this->connection->send($this->makePayload($type, $data));
    }

    /**
     * Send raw payload to server.
     *
     * @param string $payload
     * @return bool|null
     */
    public function raw(string $payload): ?bool
    {
        return $this->connection->send($payload);
    }

    /**
     * Emitted when a socket connection is successfully established.
     *
     * @param callable $handler
     * @return void
     */
    public function onConnected(callable $handler): void
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
    public function onDisconnected(callable $handler): void
    {
        $this->connection->onClose = function (AsyncTcpConnection $connection) use ($handler) {
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
        $this->connection->onError = function (AsyncTcpConnection $connection, $code, $message) use ($handler) {
            call_user_func_array($handler, [$connection, $code, $message]);
        };
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
            throw new Exception("Event '{$type}' already exists.");
        }

        $this->events[$type] = $handler;
    }

    /**
     * Connect to server and listen.
     *
     * @return void
     */
    public function listen(): void
    {
        $this->worker->onWorkerStart = function () {
            $this->connection->onMessage = function (AsyncTcpConnection $connection, string $payload) {
                $payloadData = @json_decode($payload, true);

                if ($payloadData) {
                    $payload = new Payload($payloadData);
                } else {
                    if ($this->onRawHandler) {
                        call_user_func_array($this->onRawHandler, [$payload, $connection]);
                    }
                    return;
                }

                $handler = $this->events[$payload->type] ?? null;

                if (!$handler) {
                    return;
                }

                call_user_func_array($handler, [$connection, $payload, $this]);
            };

            $this->connection->connect();
        };

        $this->worker->runAll();
    }
}