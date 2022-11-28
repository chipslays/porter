<?php

namespace Porter;

use Porter\Connection\Channels;
use Porter\Support\Collection;
use Workerman\Connection\TcpConnection;

class Connection
{
    /**
     * Constructor.
     *
     * @param TcpConnection $connection
     */
    public function __construct(protected TcpConnection $connection)
    {
        $this->connection->__connection_class_data = new Collection;
    }

    /**
     * Get connection channels manager.
     *
     * Note: Attribute `channels` init in `onConnected` server method.
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
     * Set value for this connection.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setValue(string $key, mixed $value): void
    {
        $this->connection->__connection_class_data->set($key, $value);
    }

    /**
     * Get value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        return $this->connection->__connection_class_data->get($key, $default);
    }

    /**
     * Returns true if key exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasValue(string $key): bool
    {
        return $this->connection->__connection_class_data->has($key);
    }

    /**
     * Remove private value.
     *
     * @param string $key
     * @return void
     */
    public function removeValue(string $key): void
    {
        $this->connection->__connection_class_data = $this->connection->__connection_class_data->remove($key);
    }

    /**
     * Get `TcpConnection` instance.
     *
     * @return TcpConnection
     */
    public function getTcpConnectionInstance(): TcpConnection
    {
        return $this->connection;
    }

    /**
     * Call TcpConnection methods.
     *
     * @param mixed $method
     * @param mixed $arguments
     * @return void
     */
    public function __call(mixed $method, mixed $arguments): mixed
    {
        return call_user_func_array([$this->connection, $method], $arguments);
    }

    /**
     * Get TcpConnection attribute value.
     *
     * @param mixed $attribute
     * @return mixed
     */
    public function __get(mixed $attribute): mixed
    {
        return $this->connection->{$attribute} ?? null;
    }

    /**
     * Set TcpConnection attribute value.
     *
     * @param mixed $attribute
     * @param mixed $value
     */
    public function __set(mixed $attribute, mixed $value): void
    {
        $this->connection->{$attribute} = $value;
    }
}