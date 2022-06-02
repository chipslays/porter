<?php

namespace Porter;

use Porter\Connection\Channels;
use Porter\Support\Collection;
use Workerman\Connection\TcpConnection;

class Connection
{
    public TcpConnection $connection;

    public function __construct(TcpConnection &$connection)
    {
        $this->connection = $connection;

        if (!isset($this->connection->data)) {
            $this->connection->data = new Collection;
        }
    }

    /**
     * Get connection channels manager.
     *
     * @return Channels
     */
    public function channels(): Channels
    {
        return $this->connection->channels;
    }

    /**
     * Set current channel for this connection.
     *
     * @param Channel $channel
     * @return void
     */
    public function setChannel(Channel $channel): void
    {
        $this->connection->channel = &$channel;
    }

    /**
     * Get current channel
     *
     * @return Channel|null
     */
    public function channel(): ?Channel
    {
        return $this->hasChannel() ? $this->connection->channel : null;
    }

    /**
     * Returns true if channel is set.
     *
     * @return bool
     */
    public function hasChannel(): bool
    {
        return isset($this->connection->channel);
    }

    /**
     * Remove channel from connection.
     *
     * @param bool $leaveChannel
     * @return void
     */
    public function deleteChannel(bool $leaveChannel = false): void
    {
        if ($leaveChannel) {
            $this->connection->channel->leave($this->connection);
        }

        unset($this->connection->channel);
    }

    /**
     * Leave all channels for this connection.
     *
     * @return void
     */
    public function leaveAllChannels(): void
    {
        $this->channels()->leaveAll();
    }

    /**
     * Set private value for this connection.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setValue(string $key, mixed $value): void
    {
        $this->connection->data->set($key, $value);
    }

    /**
     * Get private value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        return $this->connection->data->get($key, $default);
    }

    /**
     * Returns true if key exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasValue(string $key): bool
    {
        return $this->connection->data->has($key);
    }

    /**
     * Remove private value.
     *
     * @param string $key
     * @return void
     */
    public function removeValue(string $key): void
    {
        $this->connection->data = $this->connection->data->remove($key);
    }

    /**
     * Send raw websocket message.
     *
     * @param mixed $buffer
     * @param boolean $raw
     * @return boolean|null
     */
    public function send(mixed $buffer, bool $raw = false): bool|null
    {
        return $this->getTcpConnection()->send($buffer, $raw);
    }

    /**
     * Get `TcoConnection` instance.
     *
     * @return TcpConnection
     */
    public function getTcpConnection(): TcpConnection
    {
        return $this->connection;
    }
}