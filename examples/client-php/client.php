<?php

use Porter\Client;
use Porter\Payload;
use Workerman\Connection\AsyncTcpConnection;

require __DIR__ . '/../../vendor/autoload.php';

// use localhost instead 0.0.0.0 for connect in local env
$client = new Client('ws://localhost:3737');

$client->getWorker()::$logFile = __DIR__ . '/client.log';

$client->onConnected(function (AsyncTcpConnection $connection) use ($client) {
    $client->send('hello');
});

$client->onDisconnected(function (AsyncTcpConnection $connection) use ($client) {
    $client->send('goodbye');
});

$client->onError(function (AsyncTcpConnection $connection, $code, $message) use ($client) {
    $client->send('whoops');
});

$client->on('ping', function (AsyncTcpConnection $connection, Payload $payload, Client $client) {
    $client->send('pong');
});

$client->listen();
