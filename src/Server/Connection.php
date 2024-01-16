<?php

namespace Porter\Server;

use Closure;
use Porter\Events\Event;
use Porter\Events\Payload;
use Workerman\Connection\TcpConnection;

/**
 * @property int $id
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
        //
    }

    /**
     * Send event to this connection.
     *
     * @param string $id
     * @param array|Closure $data
     * @return void
     */
    public function event(string $id, array|Closure|Payload $data = []): void
    {
        if ($data instanceof Closure) {
            $data = call_user_func_array($data, [$this]);
        }

        if ($data instanceof Payload) {
            $data = $data->all();
        }

        $event = (new Event)
            ->setId($id)
            ->setData($data);

        $this->connection->send((string) $event);
    }

    /**
     * Returns source `TcpConnection` instance if you need it.
     *
     * @return TcpConnection
     */
    public function getTcpConnectionInstance(): TcpConnection
    {
        return $this->connection;
    }

    /**
     * Call `TcpConnection` methods.
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
     * Get `TcpConnection` attribute value.
     *
     * @param mixed $attribute
     * @return mixed
     */
    public function __get(mixed $attribute): mixed
    {
        return $this->connection->{$attribute} ?? null;
    }

    /**
     * Set `TcpConnection` attribute value.
     *
     * @param mixed $attribute
     * @param mixed $value
     */
    public function __set(mixed $attribute, mixed $value): void
    {
        $this->connection->{$attribute} = $value;
    }
}