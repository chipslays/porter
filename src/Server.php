<?php

namespace Porter;

use Closure;
use Workerman\Worker;

class Server
{
    protected Worker $worker;

    /**
     * Undocumented function
     *
     * @param string $address A valid socket address like 0.0.0.0:8080
     * @param array|null|null $context @see https://www.php.net/manual/ru/context.socket.php
     * @param int $processes @see https://www.workerman.net/doc/workerman/faq/processes-count.html
     */
    public function __construct(string $ip, int $port, array|null $context = null, int $processes = 1)
    {
        $this->worker = new Worker('websocket://' . $ip . ':' . $port, $context);

        $this->worker->count = $processes;

        $this->worker->name = 'Server-' . date('d_m_Y-H_i_s');
    }

    public function getWorker(): Worker
    {
        return $this->worker;
    }

    public function setWorker($worker): self
    {
        $this->worker = $worker;

        return $this;
    }

    public function onConnected(Closure $callback): self
    {
        return $this;
    }

    public function onDisconnected(Closure $callback): self
    {
        return $this;
    }

    public function onError(Closure $callback): self
    {
        return $this;
    }

    public function onStart(Closure $callback): self
    {
        return $this;
    }

    public function onStop(Closure $callback): self
    {
        return $this;
    }

    public function onReload(Closure $callback): self
    {
        return $this;
    }

    public function run(): void
    {
        $this->getWorker()->run();
    }
}