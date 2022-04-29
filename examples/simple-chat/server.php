<?php


use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Porter\Server;
use Porter\Terminal;

require __DIR__ . '/../../vendor/autoload.php';

require __DIR__ . '/Events/ChatMessageEvent.php';

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

$server->addEvent(ChatMessageEvent::class);

$server->start();