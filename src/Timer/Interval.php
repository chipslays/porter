<?php

namespace Porter\Timer;

use Workerman\Timer;

class Interval
{
    /**
     * Array of all defined timers.
     *
     * @var array[]
     */
    protected static $timers = [];

    /**
     * Array of running timers.
     *
     * @var int[]
     */
    protected static $running = [];

    /**
     * Add a new timer with alias.
     *
     * @param string $alias
     * @param callable $callback
     * @param float $interval
     * @param array $args
     * @param bool $persistent True - trigger interval callback, False - trigger once callback
     * @return void
     */
    public static function set(
        string $alias,
        callable $callback,
        float $interval,
        array $args = [],
        bool $persistent = true
    ): void {
        self::$timers[$alias] = [$interval, $callback, $args, $persistent];
    }

    /**
     * Run a timer immediately.
     *
     * @param string $alias
     * @param callable $callback
     * @param float $interval
     * @param array $args
     * @return void
     */
    public static function run(
        string $alias,
        callable $callback,
        float $interval,
        array $args = [],
        bool $persistent = true
    ): void {
        self::set(...func_get_args());
        self::start($alias);
    }

    /**
     * Start a timer.
     *
     * @param string $alias
     * @return bool
     */
    public static function start(string $alias): bool
    {
        if (!isset(self::$timers[$alias])) {
            return false;
        }

        $timer = self::$timers[$alias];

        $id = Timer::add(...$timer);

        if (!$id) {
            return false;
        }

        self::$running[$alias] = $id;

        return true;
    }

    /**
     * Stop timer.
     *
     * @param string $alias
     * @return bool
     */
    public static function clear(string $alias): bool
    {
        if (!isset(self::$running[$alias])) {
            return false;
        }

        $id = self::$running[$alias];

        $result = Timer::del($id);

        if ($result) {
            unset(self::$running[$alias]);
        }

        return $result;
    }
}