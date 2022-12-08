<?php

use Porter\Connection;
use Workerman\Worker;
use Porter\Server;
use Porter\Terminal;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3737');

$worker::$logFile = __DIR__ . '/server.log';

$server = Server::getInstance();
$server->boot($worker)->setLogFile(__DIR__ . '/server.log');

$server->onWebsocketConnected(function (Connection $connection, string $header) {
    // Here also available vars: $_GET, $_COOKIE, $_SERVER.
    Terminal::print("Query from client: {text:darkYellow}foo={$_GET['foo']}");
});

$server->onConnected(function (Connection $connection, string $header) {
    Terminal::print('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());
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
$server->autoloadEvents(__DIR__ . '/events');

// Or manual
// $server->addEvent(require __DIR__ . '/events/ping.php');
// $server->addEvent(require __DIR__ . '/events/hello-to.php');

$server->start();