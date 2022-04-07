<?php

use Porter\Events\Event;
use Porter\Server;
use Porter\Terminal;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

require __DIR__ . '/../../vendor/autoload.php';

require __DIR__ . '/Events/Ping.php';
require __DIR__ . '/Events/SayHello.php';

$worker = new Worker('websocket://0.0.0.0:3030');

$server = Server::getInstance();
$server->setWorker($worker);

$server->storage->path = __DIR__ . '/Storage/storage.hub';

$server->storage->put('foo', 'bar');
dump($server->storage->get('foo'));
dump($server->storage->get('foo1', 'baz'));


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
// Or:
// $server->on('ping', function (Event $event) {
//     $event->reply('pong');
// });

$server->addEvent(SayHello::class);

$server->start();