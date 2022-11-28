<?php

namespace Porter\Connection;

use Porter\Server;
use Porter\Channel;
use Porter\Connection;

class Channels
{
    /** @var string[] */
    protected array $channels = [];

    public function __construct(protected Connection $connection)
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
     * @return integer
     */
    public function count(): int
    {
        return count($this->channels);
    }

    /**
     * When connection join to channel should attach channel id to connection.
     *
     * @param string $channelId
     * @return void
     */
    public function add(string $channelId): void
    {
        $this->channels[$channelId] = $channelId;
    }

    /**
     * Method for when connection join to channel should detach channel id from connection.
     *
     * @param string $channelId
     * @return void
     */
    public function delete(string $channelId): void
    {
        unset($this->channels[$channelId]);
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