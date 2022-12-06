<?php

use Workerman\Worker;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3737');

$worker::$logFile = __DIR__ . '/server.log';

server()->setWorker($worker);

server()->onStart(function (Worker $worker) {
    timer(1, function () {
        server()->broadcast('online users', [
            'count' => server()->connections()->count(),
        ]);
    });
});

server()->start();