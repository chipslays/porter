<?php

use function porter\timer;
use function porter\server;
use function porter\connections;

require __DIR__ . '/../../vendor/autoload.php';

server()->create('0.0.0.0:3737')->setLogFile(__DIR__ . '/server.log');

server()->onStart(function () {
    timer(1, function () {
        connections()->broadcast('update date', [
            'date' => date('d.m.Y H:i:s'),
        ]);
    });
});

server()->start();