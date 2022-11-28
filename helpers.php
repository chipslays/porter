<?php

use Porter\Channel;
use Porter\Channels;
use Porter\Connection;
use Porter\Server;
use Respect\Validation\Validator;
use Workerman\Timer;
use Workerman\Worker;

if (!function_exists('porter')) {
    /**
     * Get server instance.
     *
     * @return Server
     */
    function porter(): Server
    {
        return Server::getInstance();
    }
}

if (!function_exists('server')) {
    /**
     * Get server instance.
     *
     * @return Server
     */
    function server(): Server
    {
        return Server::getInstance();
    }
}

if (!function_exists('worker')) {
    /**
     * Get worker instance.
     *
     * @return Worker
     */
    function worker(): Worker
    {
        return Server::getInstance()->getWorker();
    }
}

if (!function_exists('connection')) {
    /**
     * Get connection instance by id.
     *
     * @param int $id
     * @return Connection
     */
    function connection(int $id): Connection
    {
        return Server::getInstance()->connection($id);
    }
}

if (!function_exists('channels')) {
    /**
     * Get channels.
     *
     * @return Channels
     */
    function channels(): Channels
    {
        return Server::getInstance()->channels();
    }
}

if (!function_exists('channel')) {
    /**
     * Get channel, set & get channel value.
     *
     * @param string $id
     * @param string|array|null $key Pass array for set channel value, or string for get channel value.
     * @param mixed $default
     * @return Channel|mixed
     */
    function channel(string $id, string|array $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Server::getInstance()->channel($id);
        }

        $channel = Server::getInstance()->channel($id);

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
     * Create a timer.
     *
     * @param integer|float $interval
     * @param callable $function
     * @param mixed $args
     * @param bool $persistent
     * @return integer|bool
     */
    function timer(int|float $interval, callable $function, mixed $args = [], bool $persistent = true): int|bool
    {
        return Timer::add($interval, $function, $args, $persistent);
    }
}

if (!function_exists('copy_dir_to')) {
    /**
     * Copy dir with all (nested) files.
     *
     * @param string $src
     * @param string $dist
     * @param bool $withReplace
     * @return void
     */
    function copy_dir_to(string $src, string $dist, bool $withReplace = false) {
        $dir = opendir($src);

        @mkdir($dist);

        while ($file = readdir($dir)) {
            if (($file != '.') && ($file != '..')) {
                $srcPath = $src . '/' . $file;
                if (is_dir($srcPath)) {
                    (__FUNCTION__)($srcPath, $dist . '/' . $file);
                } else {
                    if (file_exists($dist . '/' . $file) && !$withReplace) {
                        continue;
                    }
                    copy($srcPath, $dist . '/' . $file);
                }
            }
        }

        closedir($dir);
    }
}