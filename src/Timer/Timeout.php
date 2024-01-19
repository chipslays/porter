<?php

namespace Porter\Timer;

use Closure;

class Timeout
{
    public static function set(Closure $callback, float $delay): string
    {
        $alias = md5('__timeout__' . $delay . microtime(true));

        Interval::run($alias, $callback, $delay, persistent: false);

        return $alias;
    }

    public static function clear(string $alias): void
    {
        Interval::clear($alias);
    }
}