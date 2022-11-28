<?php

use Porter\Terminal;
use Porter\Connection;
use Workerman\Worker;

require __DIR__ . '/bootstrap.php';

server()->onConnected(function (Connection $connection) {
    Terminal::print('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());
});

server()->onDisconnected(function (Connection $connection) {
    Terminal::print("{text:darkRed}Disconnected: " . $connection->getRemoteAddress());
});

server()->onError(function (Connection $connection, $code, $message) {
    Terminal::print("{bg:red}{text:white}Error {$code} {$message}");
});

server()->onStart(function (Worker $worker) {
    Terminal::print("{text:darkGreen}Server started...");
});

server()->start();