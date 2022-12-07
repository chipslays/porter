<?php

namespace Porter;

use Workerman\Timer as WorkermanTimer;

/**
 * Wrapper over Workerman Timer with aliases.
 */
class Timer
{
    /**
     * @var array[]
     */
    protected static $timers = [];

    /**
     * @var int[]
     */
    protected static $running = [];

    /**
     * Add timer.
     *
     * @param string $alias
     * @param float $interval
     * @param callable $callback
     * @param array $args
     * @return void
     */
    public static function add(string $alias, float $interval, callable $callback, array $args = [], bool $persistent = true): void
    {
        self::$timers[$alias] = [$interval, $callback, $args, $persistent];
    }

    /**
     * Run timer.
     *
     * @param string $alias
     * @return bool
     */
    public static function run(string $alias): bool
    {
        if (!isset(self::$timers[$alias])) {
            return false;
        }

        $timer = self::$timers[$alias];

        $id = WorkermanTimer::add(...$timer);

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
    public static function stop(string $alias): bool
    {
        if (!isset(self::$running[$alias])) {
            return false;
        }

        $id = self::$running[$alias];

        $result = WorkermanTimer::del($id);

        if ($result) {
            unset(self::$running[$alias]);
        }

        return $result;
    }
}