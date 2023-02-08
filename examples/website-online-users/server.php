<?php

use Workerman\Worker;

use function porter\server;
use function porter\timer;

require __DIR__ . '/../../vendor/autoload.php';

server()->create('0.0.0.0:3737')->setLogFile(__DIR__ . '/server.log');

server()->onStart(function (Worker $worker) {
    timer(1, function () {
        server()->broadcast('online users', [
            'count' => server()->connections()->count(),
        ]);
    });
});

server()->start();