<?php

use Workerman\Worker;

ob_start();
require_once __DIR__ . '/../public/index.php';
ob_end_clean();

if ($_ENV['IS_SERVER'] == 'true') {
    $context = [
        'ssl' => array(
            'local_cert' => '/etc/letsencrypt/live/example.com/cert.pem',
            'local_pk' => '/etc/letsencrypt/live/example.com/privkey.pem',
            'verify_peer' => false,
        )
    ];
    $worker = new Worker('websocket://0.0.0.0:3031', $context);
    $worker->transport = 'ssl';
} else {
    $worker = new Worker('websocket://0.0.0.0:3031');
}

$worker->count = 1;

server()->setWorker($worker);

foreach (glob(__DIR__  . '/Events/*Event.php') as $file) {
    require_once $file;
}