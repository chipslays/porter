<?php

use Porter\Connection;
use Porter\Events\Event;
use Workerman\Worker;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3737');

$worker::$logFile = __DIR__ . '/server.log';

server()->boot($worker);

server()->onConnected(function (Connection $connection, string $header) {
    cprint('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());
});

server()->onDisconnected(function (Connection $connection) {
    cprint('{text:darkRed}Disconnected: ' . $connection->getRemoteAddress());
});

server()->on('ping', function (Event $event) {
    $event->reply('pong');
});

server()->start();