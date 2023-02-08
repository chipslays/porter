<?php

use Porter\Connection;
use Porter\Events\Event;
use Workerman\Worker;

use function porter\cprint;
use function porter\server;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3737');

server()->boot($worker)->setLogFile(__DIR__ . '/server.log');

server()->onConnected(function (Connection $connection) {
    cprint('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());
});

server()->onDisconnected(function (Connection $connection) {
    cprint('{text:darkRed}Disconnected: ' . $connection->getRemoteAddress());
});

server()->on('ping', function (Event $event) {
    $event->reply('pong');
});

server()->start();