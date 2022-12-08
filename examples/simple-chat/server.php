<?php

use Porter\Connection;
use Workerman\Worker;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3737');


server()->boot($worker)->setLogFile(__DIR__ . '/server.log');

server()->onConnected(function (Connection $connection) {
    $connection->nickname = 'Anonymous#' . $connection->id;

    server()->broadcast('chat message', [
        'nickname' => 'Notification',
        'message' => "{$connection->nickname} has joined.",
    ]);
});

server()->onDisconnected(function (Connection $connection) {
    server()->broadcast('chat message', [
        'nickname' => 'Notification',
        'message' => "{$connection->nickname} has left.",
    ]);
});

server()->onStart(function (Worker $worker) {
    timer(1, function () {
        server()->broadcast('update users count', [
            'count' => server()->connections()->count(),
        ]);
    });
});

server()->autoloadEvents(__DIR__ . '/events');

server()->start();