<?php

namespace Porter;

use Porter\Connection\Channels;
use Porter\Support\Collection;
use Workerman\Connection\TcpConnection;

/**
 * @property int $id
 * @property Collection $data
 * @property Channels $channels
 *
 * @method int|string getStatus(bool $raw_output = true)
 * @method bool|null send(mixed $send_buffer, bool $raw = false)
 * @method string getRemoteIp()
 * @method int getRemotePort()
 * @method string getRemoteAddress()
 * @method string getLocalIp()
 * @method int getLocalPort()
 * @method string getLocalAddress()
 * @method int getSendBufferQueueSize()
 * @method int getRecvBufferQueueSize()()
 * @method bool isIpV4()
 * @method bool isIpV6()
 * @method void pauseRecv()
 * @method void baseRead(resource $socket, bool $check_eof = true)
 * @method void|bool baseWrite()
 * @method bool doSslHandshake(resource $socket)
 * @method void pipe(self $dest)
 * @method void consumeRecvBuffer(int $length)
 * @method void close(mixed $data = null, bool $raw = false)
 * @method resource getSocket()
 * @method void checkBufferWillFull()
 * @method bool bufferIsFull()
 * @method bool bufferIsEmpty()
 * @method void destroy()
 */
class Connection
{
    /**
     * Constructor.
     *
     * @param TcpConnection $connection
     */
    public function __construct(protected TcpConnection $connection)
    {
        if (!isset($this->connection->data)) {
            $this->connection->data = new Collection;
        }

        if (!isset($this->connection->channels)) {
            $this->connection->channels = new Channels($this->connection);
        }
    }

    /**
     * Reply event to this connection.
     *
     * @param string|null $event
     * @param array $data
     * @return self
     */
    public function reply(string $event, array $data = []): self
    {
        Server::getInstance()->to($this->connection, $event, $data);

        return $this;
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
     * Set value for this connection.
     *
     * @param mixed $key
     * @param mixed $value
     * @return self
     */
    public function set(mixed $key, mixed $value): self
    {
        $this->connection->data->set($key, $value);

        return $this;
    }

    /**
     * Get value.
     *
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public function get(mixed $key, mixed $default = null): mixed
    {
        return $this->connection->data->get($key, $default);
    }

    /**
     * Returns true if key exists.
     *
     * @param mixed $key
     * @return bool
     */
    public function has(mixed $key): bool
    {
        return $this->connection->data->has($key);
    }

    /**
     * Remove private value.
     *
     * @param mixed $key
     * @return self
     */
    public function remove(mixed $key): self
    {
        $this->connection->data = $this->connection->data->remove($key);

        return $this;
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