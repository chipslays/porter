<?php

use Workerman\Worker;

use function porter\server;

require __DIR__.'/../vendor/autoload.php';

// setup laravel app
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

// configure worker
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

// boot websocket server
server()->boot($worker);

// generate log file path
$logFile = storage_path('logs/porter/' . $worker->name . '.log');

// create logs directory
if (!file_exists($logDir = dirname($logFile))) {
    mkdir($logDir, 0666);
}

// cleanup empty logs
foreach (glob($logDir . '/*.log') as $file) {
    if (filesize($file) === 0){
        unlink($file);
    }
}

// set log file
server()->setLogFile(__DIR__ . '/server.log');

// load event classes
server()->autoloadEvents(__DIR__ . '/core/events');

// load server actions
foreach (glob(__DIR__ . '/core/actions/*.php') as $file) {
    require_once $file;
}

// load timers
foreach (glob(__DIR__ . '/core/timers/*.php') as $file) {
    require_once $file;
}

// load handler (server start point)
require_once __DIR__ . '/core/handler.php';

// start server and handle events
server()->start();