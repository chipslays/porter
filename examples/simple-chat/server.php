<?php


use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Porter\Server;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3030');

$server = Server::getInstance();
$server->setWorker($worker);

$server->onConnected(function (TcpConnection $connection) {
    $connection->nickname = 'Anonymous#' . mt_rand(1000, 9999);

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

$server->autoload(__DIR__ . '/Events');

$server->start();