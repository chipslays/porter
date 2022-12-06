<?php

use Workerman\Worker;

ob_start();
require_once __DIR__ . '/../public/index.php';
ob_end_clean();

if (isset($_ENV['PORTER_SSL']) && $_ENV['PORTER_SSL'] == 'true') {
    $context = [
        'ssl' => [
            'local_cert' => $_ENV['PORTER_LOCAL_CERT'],
            'local_pk' => $_ENV['PORTER_LOCAL_PK'],
            'verify_peer' => false,
        ],
    ];

    $worker = new Worker('websocket://' . $_ENV['PORTER_HOST'] ?? '0.0.0.0' . ':' . $_ENV['PORTER_PORT'] ?? '3737', $context);
    $worker->transport = 'ssl';
} else {
    $worker = new Worker('websocket://' . $_ENV['PORTER_HOST'] ?? '0.0.0.0' . ':' . $_ENV['PORTER_PORT'] ?? '3737');
}

server()->boot($worker);

$logFile = storage_path('logs/porter/' . $worker->name . '.log');
$logDir = dirname($logFile);

if (!file_exists($logDir)) {
    mkdir($logDir, 0666);
}

$worker::$logFile = $logFile;

server()->autoloadEvents(__DIR__ . '/events');