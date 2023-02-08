<?php

use Porter\Events\Event;
use Workerman\Worker;

use function porter\server;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3737');

server()->boot($worker)->setLogFile(__DIR__ . '/server.log');

server()->on('ping', function (Event $event) {
    $event->reply('pong');
});

server()->on('hello to', function (Event $event) {
    $event->reply(data: [
        'message' => "Hello, {$event->payload->data['username']}!"
    ]);
});

server()->start();