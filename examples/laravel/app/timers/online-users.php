<?php

use Porter\Timer;

Timer::add('online users', 1, function () {
    server()->broadcast('online users', [
        'count' => server()->connections()->count(),
    ]);
});