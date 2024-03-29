<?php

namespace porter;

use Porter\Server;
use Porter\Channel;
use Porter\Channels;
use Porter\Connection;
use Porter\Connections;
use Porter\Terminal;
use Workerman\Timer;
use Workerman\Worker;
use Respect\Validation\Validator;

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

if (!function_exists('connections')) {
    /**
     * Get all connections on server.
     *
     * @return Connections
     */
    function connections(): Connections
    {
        return Server::getInstance()->connections();
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
     * Get channel.
     *
     * @param string $id
     * @return Channel|null
     */
    function channel(string $id): ?Channel
    {
        return Server::getInstance()->channel($id);
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
        return Server::getInstance()->validator();
    }
}

if (!function_exists('timer')) {
    /**
     * Create a timer.
     *
     * Alias for `Workerman\Timer`.
     *
     * @param float $interval Interval in seconds.
     * @param callable $function Execute every `$interval` seconds.
     * @param mixed $args Callback arguments.
     * @param bool $persistent Execute one time.
     * @return int|bool Returns timer id.
     */
    function timer(float $interval, callable $callback, mixed $args = [], bool $persistent = true): int|bool
    {
        return Timer::add($interval, $callback, $args, $persistent);
    }
}

if (!function_exists('timer_delete')) {
    /**
     * Delete a timer by id.
     *
     * Alias for `Workerman\Timer`.
     *
     * @param int $id Timer id
     * @return bool
     */
    function timer_delete(int $id): bool
    {
        return Timer::del($id);
    }
}

if (!function_exists('timer_delete_all')) {
    /**
     * Delete all timers.
     *
     * Alias for `Workerman\Timer`.
     *
     * @return bool
     */
    function timer_delete_all(): void
    {
        Timer::delAll();
    }
}

if (!function_exists('timeout')) {
    /**
     * One time execute callback after N seconds without run block.
     *
     * @param float $seconds
     * @param callable $callback
     * @param array $args
     * @return int|boolean
     */
    function timeout(float $seconds, callable $callback, array $args = []): int|bool
    {
        return Timer::add($seconds, $callback, $args, false);
    }
}

if (!function_exists('cprint')) {
    /**
     * Print colorful text with auto reset styles on end.
     *
     * Codes list:
     * `{reset}`
     * `{text:black}`
     * `{text:white}`
     * `{text:darkGrey}`
     * `{text:darkGray}`
     * `{text:grey}`
     * `{text:gray}`
     * `{text:darkRed}`
     * `{text:red}`
     * `{text:darkGreen}`
     * `{text:green}`
     * `{text:darkYellow}`
     * `{text:yellow}`
     * `{text:blue}`
     * `{text:darkMagenta}`
     * `{text:magenta}`
     * `{text:darkCyan}`
     * `{text:cyan}`
     * `{bg:black}`
     * `{bg:red}`
     * `{bg:green}`
     * `{bg:yellow}`
     * `{bg:blue}`
     * `{bg:magenta}`
     * `{bg:cyan}`
     * `{bg:grey}`
     * `{bg:gray}`
     *
     * @param mixed $text
     * @return void
     */
    function cprint(mixed $text): void
    {
        Terminal::print($text);
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
    function copy_dir_to(string $src, string $dist, bool $withReplace = false)
    {
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
