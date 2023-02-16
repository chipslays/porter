<?php

namespace Porter\Connection;

use Porter\Server;
use Porter\Channel;
use Porter\Connection;
use Workerman\Connection\TcpConnection;

class Channels
{
    /**
     * @var string[]
     */
    protected array $channels = [];

    /**
     * Constructor.
     *
     * @param TcpConnection|Connection $connection
     */
    public function __construct(protected TcpConnection|Connection $connection)
    {
        //
    }

    /**
     * Get connection (user) channels.
     *
     * @return Channel[]
     */
    public function all(): array
    {
        $channels = [];

        foreach ($this->channels as $id) {
            $channels[] = Server::getInstance()->channel($id);
        }

        return $channels;
    }

    /**
     * Get channels count.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->channels);
    }

    /**
     * When connection join to channel should attach channel id to connection.
     *
     * @param string $channelId
     * @return self
     */
    public function attach(string $channelId): self
    {
        $this->channels[$channelId] = $channelId;

        return $this;
    }

    /**
     * Method for when connection leave the channel should detach channel id from connection.
     *
     * @param string $channelId
     * @return self
     */
    public function detach(string $channelId): self
    {
        unset($this->channels[$channelId]);

        return $this;
    }

    /**
     * Leave all channels for this connection.
     *
     * @return void
     */
    public function leaveAll(): void
    {
        /** @var Channel $channel */
        foreach ($this->all() as $channel) {
            $channel->leave($this->connection);
        }
    }
}