<?php

namespace Porter;

use Workerman\Connection\TcpConnection;

class Channels
{
    /** @var Channel[] */
    public array $channels = [];

    /**
     * Constructor.
     *
     * @param Server $server
     */
    public function __construct(protected Server $server)
    {

    }

    /**
     * Get array of channels.
     *
     * @return Channel[]
     */
    public function all(): array
    {
        return $this->channels;
    }

    /**
     * Get count of channels.
     *
     * @return integer
     */
    public function count(): int
    {
        return count($this->channels);
    }

    /**
     * Create new channel.
     *
     * @param string $id
     * @param array $data
     * @return Channel
     */
    public function create(string $id, array $data = []): Channel
    {
        $channel = new Channel($id, $data);
        $this->channels[$id] = $channel;

        return $channel;
    }

    /**
     * Delete channel.
     *
     * @param string $id
     * @return void
     */
    public function delete(string $id): void
    {
        unset($this->channels[$id]);
    }

    /**
     * Get a channel.
     *
     * @param string $id
     * @return Channel|null
     */
    public function get(string $id): ?Channel
    {
        return $this->channels[$id] ?? null;
    }

    /**
     * Checks if given channel id exists already.
     *
     * @param string $id
     * @return boolean
     */
    public function exists(string $id): bool
    {
        return isset($this->channels[$id]);
    }

    /**
     * Join or create and join to channel.
     *
     * @param string $id
     * @param TcpConnection $connection
     * @return Channel
     */
    public function join(string $id, TcpConnection $connection): Channel
    {
        if ($this->exists($id)) {
            $channel = $this->get($id)->join($connection);
        } else {
            $channel = $this->create($id)->join($connection);
        }

        return $channel;
    }
}