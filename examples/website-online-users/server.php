<?php

use Workerman\Worker;

use function porter\server;
use function porter\timer;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3737');

server()->boot($worker)->setLogFile(__DIR__ . '/server.log');

server()->onStart(function (Worker $worker) {
    timer(1, function () {
        server()->broadcast('online users', [
            'count' => server()->connections()->count(),
        ]);
    });
});

server()->start();