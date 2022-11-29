<?php

use Porter\Connection;
use Workerman\Worker;
use Porter\Server;
use Porter\Terminal;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3737');

$server = Server::getInstance();
$server->setWorker($worker);

$server->onConnected(function (Connection $connection, string $header) {
    Terminal::print('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());

    // Here also available vars: $_GET, $_COOKIE, $_SERVER.
    Terminal::print("Query from client: {text:darkYellow}foo={$_GET['foo']}");
});

$server->onDisconnected(function (Connection $connection) {
    Terminal::print('{text:darkRed}Disconnected: ' . $connection->getRemoteAddress());
});

$server->onError(function (Connection $connection, $code, $message) {
    Terminal::print("{bg:red}{text:white}Error {$code} {$message}");
});

$server->onRaw(function (string $payload, Connection $connection) {
    $connection->send($payload);
});

// Autoload event classes
// $server->autoloadEvents(__DIR__ . '/Events');

// Or manual
// $server->addEvent(require __DIR__ . '/Events/Ping.php');
// $server->addEvent(require __DIR__ . '/Events/HelloTo.php');

$server->start();