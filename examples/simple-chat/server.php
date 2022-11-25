<?php

use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Porter\Server;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3737');

$server = Server::getInstance();
$server->setWorker($worker);

$server->onConnected(function (TcpConnection $connection) {
    $connection->nickname = 'Anonymous#' . $connection->id;

    server()->broadcast('chat message', [
        'nickname' => 'Notification',
        'message' => "{$connection->nickname} has joined.",
    ]);
});

$server->onDisconnected(function (TcpConnection $connection) {
    server()->broadcast('chat message', [
        'nickname' => 'Notification',
        'message' => "{$connection->nickname} has left.",
    ]);
});

$server->onStart(function (Worker $worker) {
    timer(1, function () {
        server()->broadcast('update users count', [
            'count' => count(server()->connections()),
        ]);
    });
});

$server->autoloadEvents(__DIR__ . '/Events');

$server->start();