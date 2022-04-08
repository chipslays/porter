<?php

namespace Porter\Events;

use Porter\Channel;
use Porter\Server;
use Porter\Payload;
use Porter\Traits\Payloadable;
use Workerman\Connection\TcpConnection;

abstract class AbstractEvent
{
    use Payloadable;

    /**
     * Available if client passed `channelId`.
     *
     * @var Channel|null
     */
    public ?Channel $channel;

    /**
     * Available if client passed `targetId`.
     *
     * @var TcpConnection|null
     */
    public ?TcpConnection $target;

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
    ) {
        $this->server = Server::getInstance();

        // Get channel instance by `channelId` parameter.
        $this->channel = $this->server->channels->get($payload->data['channelId'] ?? '');

        // Get target connection instance by `targetId` parameter.
        $this->target = isset($payload->data['targetId']) ? $this->server->getConnection((int) $payload->data['targetId']) : null;
    }

    /**
     * Handle incoming event from client.
     *
     * @return void
     */
    abstract public function handle(TcpConnection $connection, Payload $payload, Server $server): void;

    /**
     * Send event to connection.
     *
     * @param TcpConnection $connection
     * @param string $event
     * @param array $data
     * @return bool|null
     */
    public function to(TcpConnection $connection, string $event, array $data = []): bool|null
    {
        return $connection->send($this->makePayload($event, $data));
    }

    /**
     * Reply event to incoming connection.
     *
     * @param string $event
     * @param array $data
     * @return bool|null
     */
    public function reply(string $event, array $data = []): bool|null
    {
        return $this->to($this->connection, $event, $data);
    }

    /**
     * Send raw data to connection.
     *
     * @param string $string
     * @return bool|null
     */
    public function raw(string $string): bool|null
    {
        return $this->connection->send($string);
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

    /**
     * Getter for channel (available if client passed `channelId`).
     *
     * @return Channel|null
     */
    public function channel(): ?Channel
    {
        return $this->channel;
    }

    /**
     * Getter for target (available if client passed `targetId`).
     *
     * @return TcpConnection|null
     */
    public function target(): ?TcpConnection
    {
        return $this->target;
    }
}
