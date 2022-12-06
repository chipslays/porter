<?php

use Porter\Events\Event;
use Workerman\Worker;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3737');

$worker::$logFile = __DIR__ . '/server.log';

server()->boot($worker);

server()->on('ping', function (Event $event) {
    $event->reply('pong');
});

server()->on('hello to', function (Event $event) {
    $event->reply(data: [
        'message' => "Hello, {$event->payload->data['username']}!"
    ]);
});

server()->start();