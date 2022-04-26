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

// storage
$server->storage->path = __DIR__ . '/Storage/storage.hub';
$server->storage->put('foo', 'bar');
dump($server->storage->get('foo')); // bar
dump($server->storage->get('foo1', 'baz')); // baz

$server->onConnected(function (TcpConnection $connection) {
    Terminal::print('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());
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
// Or:
// $server->on('ping', function (Event $event) {
//     $event->reply('pong');
// });


$server->start();