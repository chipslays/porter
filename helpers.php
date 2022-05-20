<?php

use Porter\Channel;
use Porter\Channels;
use Porter\Server;
use Respect\Validation\Validator;
use Workerman\Timer;
use Workerman\Worker;

if (!function_exists('porter')) {
    /**
     * @return Server
     */
    function porter(): Server
    {
        return Server::getInstance();
    }
}

if (!function_exists('server')) {
    /**
     * @return Server
     */
    function server(): Server
    {
        return Server::getInstance();
    }
}

if (!function_exists('worker')) {
    /**
     * @return Worker
     */
    function worker(): Worker
    {
        return Server::getInstance()->getWorker();
    }
}

if (!function_exists('channels')) {
    function channels(): Channels
    {
        return Server::getInstance()->channels();
    }
}

if (!function_exists('channel')) {
    /**
     * @param string $id
     * @param string|array|null $key
     * @param mixed $default
     * @return Channel|mixed
     */
    function channel(string $id, string|array $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Server::getInstance()->channels->get($id);
        }

        $channel = Server::getInstance()->channels->get($id);

        if (!$channel) {
            return null;
        }

        if (is_array($key)) {
            $channel->data->set($key[0], $key[1]);
            return null;
        }

        return $channel->data->get($key, $default);
    }
}

if (!function_exists('validator')) {
    /**
     * Create validator instance.
     *
     * @return Validator
     */
    function validator(): Validator
    {
        return Server::getInstance()->validator::create();
    }
}

if (!function_exists('timer')) {
    /**
     * Add a timer.
     *
     * @param integer|float $interval
     * @param callable $function
     * @param mixed $args
     * @param boolean $persistent
     * @return integer|boolean
     */
    function timer(int|float $interval, callable $function, mixed $args = [], bool $persistent = true): int|bool
    {
        return Timer::add($interval, $function, $args, $persistent);
    }
}
