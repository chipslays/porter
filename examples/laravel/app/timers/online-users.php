<?php

use Porter\Timer;

use function porter\server;
use function porter\connections;

Timer::add('online users', 1, function () {
    server()->broadcast('online users', [
        'count' => connections()->count(),
    ]);
});