<?php

namespace Porter;

use Workerman\Connection\TcpConnection;

class Channels
{
    /**
     * Array of channels.
     *
     * @var Channel[]
     */
    public array $channels = [];

    /**
     * Constructor.
     *
     * @param Server $server
     */
    public function __construct(protected Server $server)
    {
        //
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
     * @return int
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
        if (!$this->exists($id)) {
            return;
        }

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
     * @return bool
     */
    public function exists(string $id): bool
    {
        return isset($this->channels[$id]);
    }

    /**
     * Join or create and join to channel.
     *
     * @param string $id
     * @param TcpConnection|Connection|array $connections
     * @return Channel
     */
    public function join(string $id, TcpConnection|Connection|array $connections): Channel
    {
        if ($this->exists($id)) {
            $channel = $this->get($id)->join($connections);
        } else {
            $channel = $this->create($id)->join($connections);
        }

        return $channel;
    }
}