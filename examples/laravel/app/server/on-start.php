<?php

use Workerman\Worker;

server()->onStart(function (Worker $worker) {
    cprint("{text:darkGreen}Server started...");
});