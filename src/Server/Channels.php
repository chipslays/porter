<?php

namespace Porter\Server;

class Channels
{
    /**
     * Array of channels.
     *
     * @var Channel[]
     */
    protected array $channels = [];

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
     * @return Channel
     */
    public function create(string $id): Channel
    {
        $channel = new Channel($id);

        $this->channels[$id] = $channel;

        return $this->channels[$id];
    }

    /**
     * Create channel if not exists, get channel if exists.
     *
     * @param string $id
     * @return Channel
     */
    public function createOrGet(string $id): Channel
    {
        return $this->channels[$id] ?? $this->create($id);
    }

    /**
     * Delete channel.
     *
     * @param Channel|string $id
     * @return void
     */
    public function remove(Channel|string $channel): void
    {
        if ($channel instanceof Channel) {
            $channel = $channel->id();
        }

        unset($this->channels[$channel]);
    }

    /**
     * Destroy and remove channel.
     *
     * @param Channel|string $channel
     * @return void
     */
    public function destroy(Channel|string $channel): void
    {
        if (is_string($channel)) {
            $channel = $this->get($channel);
        }

        $channel->leave($channel->connections());

        unset($this->channels[$channel->id()]);
        unset($channel);
    }

    /**
     * Get a channel.
     *
     * @param string $id
     * @return Channel|null
     */
    public function get(string $id): ?Channel
    {
        $channel = $this->channels[$id] ?? null;

        return $channel;
    }

    /**
     * @param Channel $channel
     * @return self
     */
    public function add(Channel $channel): self
    {
        $this->channels[$channel->id()] = $channel;

        return $this;
    }

    /**
     * Checks that the channel exists.
     *
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return isset($this->channels[$id]);
    }
}