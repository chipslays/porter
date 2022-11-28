<?php

use Workerman\Worker;
use Porter\Server;
use Porter\Storage;
use Porter\Terminal;

require __DIR__ . '/../../vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3737');

$server = Server::getInstance();
$server->setWorker($worker);

// set path only after you set worker instance
// *note: if you not provide path or an incorrect path, the data will be stored in RAM, and the data will be lost upon restart
$server->storage->setPath(__DIR__ . '/storage/storage.hub');

// add value to storage
$server->storage->put('foo', 'bar');

// get value from storage
Terminal::print($server->storage->get('foo')); // bar

// get default value if value not exists in storage
Terminal::print($server->storage->get('foo1', 'baz')); // baz

// check value
Terminal::print($server->storage->has('foo')); // true
Terminal::print($server->storage->has('baz')); // false

// returns string if path not empty.
Terminal::print($server->storage->getPath());

// remove storage file from disk
Terminal::print($server->storage->deleteLocalFile());

// you can use Storage class as standalone anywhere.
$storage1 = new Storage(__DIR__ . '/storage/storage1.hub');
$storage2 = new Storage(__DIR__ . '/storage/storage2.hub');
$storage3 = new Storage(__DIR__ . '/storage/storage3.hub');