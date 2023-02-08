<?php

use Porter\Connection;
use Workerman\Worker;

use function porter\server;
use function porter\timer;

require __DIR__ . '/../../vendor/autoload.php';

server()->create('0.0.0.0:3737')->setLogFile(__DIR__ . '/server.log');

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