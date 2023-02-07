<?php

namespace Porter\Traits;

trait Rawable
{
    protected $onRawHandler;

    /**
     * Handle raw data from client.
     *
     * @param callable $handler
     * @return void
     */
    public function onRaw(callable $handler): void
    {
        $this->onRawHandler = $handler;
    }
}