<?php

use Porter\Timer;
use Workerman\Worker;

server()->onStart(function (Worker $worker) {
    Timer::run('online users');
    cprint("{text:darkGreen}Server started...");
});