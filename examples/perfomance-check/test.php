<?php

use Workerman\Worker;
use Workerman\Connection\AsyncTcpConnection;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker();

$worker->onWorkerStart = function(Worker $worker) {
    $connectionCount = 300; // windows supports max. 255 connections

    $clients = [];

    for ($i = 0; $i < $connectionCount; $i++) {
        /** @var AsyncTcpConnection */
        $clients[$i] = new AsyncTcpConnection('ws://localhost:3737');
        $clients[$i]->startAt = microtime(true);
        $clients[$i]->connect();
        $clients[$i]->send(json_encode(['type' => 'ping']));
        $clients[$i]->onMessage = function(AsyncTcpConnection $connection, string $payload) {
            cprint("{$connection->id} | {text:darkGreen}" . round(microtime(true) - $connection->startAt, 4) . " {reset}| {text:blue}payload: {$payload}");
        };
    }
};

$worker->runAll();