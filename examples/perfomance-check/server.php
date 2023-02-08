<?php

use Porter\Connection;
use Porter\Events\Event;
use Workerman\Worker;

use function porter\cprint;
use function porter\server;

require __DIR__ . '/../../vendor/autoload.php';

server()->create('0.0.0.0:3737')->setLogFile(__DIR__ . '/server.log');

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