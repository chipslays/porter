<?php

use Workerman\Worker;
use Porter\Server;
use Porter\Storage;
use Porter\Terminal;

require __DIR__ . '/../../vendor/autoload.php';

$server = Server::getInstance();
$server->create('0.0.0.0:3737');

// set path only after booting server
$server->storage()->load(__DIR__ . '/storage/storage.hub');

// add value to storage
$server->storage()->put('foo', 'bar');

// get value from storage
Terminal::print($server->storage()->get('foo')); // bar

// get default value if value not exists in storage
Terminal::print($server->storage()->get('foo1', 'baz')); // baz

// check value
Terminal::print($server->storage()->has('foo')); // true
Terminal::print($server->storage()->has('baz')); // false

$server->storage()->save();

// returns string if path not empty.
Terminal::print($server->storage()->filename());

// you can use Storage class as standalone anywhere.
$storage1 = new Storage(__DIR__ . '/storage/storage1.hub');
$storage2 = new Storage(__DIR__ . '/storage/storage2.hub');
$storage3 = new Storage(__DIR__ . '/storage/storage3.hub');