<?php

namespace Porter\Traits;

trait Rawable
{
    protected $onRawHandler;

    public function onRaw(callable $handler): void
    {
        $this->onRawHandler = $handler;
    }
}