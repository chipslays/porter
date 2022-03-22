<?php

use Porter\Server;
use Porter\Terminal;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/Events/Ping.php';
require __DIR__ . '/Events/SayHello.php';

$worker = new Worker('websocket://0.0.0.0:3030');

$server = Server::getInstance();

$server->setWorker($worker);

$server->onConnected(function (TcpConnection $connection) {
    Terminal::print('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());
});

$server->onDisconnected(function (TcpConnection $connection) {
    Terminal::print('{text:darkRed}Disconnected: ' . $connection->getRemoteAddress());
});

$server->onError(function (TcpConnection $connection, $code, $message) {
    Terminal::print("{bg:red}{text:white}Error {$code} {$message}");
});

$server->addEvent(Ping::class);
$server->addEvent(SayHello::class);

$server->start();