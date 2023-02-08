<?php

use Porter\Timer;
use Workerman\Worker;

use function porter\cprint;
use function porter\server;

server()->onStart(function (Worker $worker) {
    Timer::run('online users');
    cprint("{text:darkGreen}Server started...");
});