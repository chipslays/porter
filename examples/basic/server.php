<?php


use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Porter\Server;
use Porter\Terminal;
use Porter\Events\Event;

require __DIR__ . '/../../vendor/autoload.php';

require __DIR__ . '/Events/PingEvent.php';
require __DIR__ . '/Events/HelloToEvent.php';

$worker = new Worker('websocket://0.0.0.0:3030');

$server = Server::getInstance();
$server->setWorker($worker);

$server->onConnected(function (TcpConnection $connection, string $header) {
    Terminal::print('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());

    // Here also available vars: $_GET, $_COOKIE, $_SERVER.
    Terminal::print("Query from client: {text:darkYellow}foo={$_GET['foo']}");
});

$server->onDisconnected(function (TcpConnection $connection) {
    Terminal::print('{text:darkRed}Disconnected: ' . $connection->getRemoteAddress());
});

$server->onError(function (TcpConnection $connection, $code, $message) {
    Terminal::print("{bg:red}{text:white}Error {$code} {$message}");
});

$server->onRaw(function (string $payload, TcpConnection $connection) {
    $connection->send($payload);
});

$server->addEvent(HelloToEvent::class);
$server->addEvent(PingEvent::class);

// Or you can use callback instead event class:
// $server->on('ping', function (Event $event) {
//     $event->reply('pong');
// });

$server->start();