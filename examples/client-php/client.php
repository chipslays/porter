<?php

use Porter\Client;
use Porter\Payload;
use Workerman\Connection\AsyncTcpConnection;

require __DIR__ . '/../../vendor/autoload.php';

// use localhost instead 0.0.0.0 for client
$client = new Client('ws://localhost:3737');

// send event as client on connect
$client->onConnected(fn () => $client->event('ping'));

// handle answer event from server
$client->on('pong', function (AsyncTcpConnection $connection, Payload $payload, Client $client) {
    echo 'PONG!' . PHP_EOL;
});

// connect to websocket server
$client->listen();
