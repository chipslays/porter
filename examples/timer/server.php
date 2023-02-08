<?php

use Workerman\Worker;

use function porter\connections;
use function porter\server;
use function porter\timer;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3737');

server()->boot($worker)->setLogFile(__DIR__ . '/server.log');

server()->onStart(function () {
    timer(1, function () {
        connections()->broadcast('update date', [
            'date' => date('d.m.Y H:i:s'),
        ]);
    });
});

server()->start();