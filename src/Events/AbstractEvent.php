<?php

namespace Porter\Events;

use Porter\Server;
use Porter\Payload;
use Porter\Traits\Payloadable;
use Workerman\Connection\TcpConnection;

abstract class AbstractEvent
{
    use Payloadable;

    /**
     * Event name.
     *
     * @var string
     */
    public static string $name;

    /**
     * @var Server
     */
    public Server $server;

    /**
     * Constructor.
     *
     * @param TcpConnection $connection
     * @param Payload $payload
     */
    public function __construct(
        public TcpConnection $connection,
        public Payload $payload,
    )
    {
        $this->server = Server::getInstance();
    }

    /**
     * Handle incoming event from client.
     *
     * @return void
     */
    abstract public function handle(TcpConnection $connection, Payload $payload, Server $server): void;

    /**
     * Send event by connection.
     *
     * @param TcpConnection $connection
     * @param string $event
     * @param array $data
     * @return void
     */
    public function to(TcpConnection $connection, string $event, array $data = []): void
    {
        $connection->send($this->makePayload($event, $data));
    }

    /**
     * Reply event to incoming connection.
     *
     * @param string $event
     * @param array $data
     * @return void
     */
    public function reply(string $event, array $data = [])
    {
        $this->to($this->connection, $event, $data);
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

        foreach ($this->server->getWorker()->connections as $connection) {
            if (in_array($connection->id, $excepts)) continue;
            $this->to($connection, $event, $data);
        }
    }
}