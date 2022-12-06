<?php

use Workerman\Worker;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

if (env('PORTER_TRANSPORT') == 'ssl') {
    $context = [
        'ssl' => [
            'local_cert' => env('PORTER_CERTIFICATE'),
            'local_pk' => env('PORTER_PRIVATE_KEY'),
            'verify_peer' => false,
        ],
    ];
}

$worker = new Worker('websocket://' . env('PORTER_HOST', '0.0.0.0') . ':' . env('PORTER_PORT', '3737'), $context ?? []);
$worker->transport = env('PORTER_TRANSPORT', 'tcp');

server()->boot($worker);

$logFile = storage_path('logs/porter/' . $worker->name . '.log');

if (!file_exists($logDir = dirname($logFile))) {
    mkdir($logDir, 0666);
}

$worker::$logFile = $logFile;

server()->autoloadEvents(__DIR__ . '/events');

require_once __DIR__ . '/kernel.php';

server()->start();