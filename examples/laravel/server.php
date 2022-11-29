<?php

use Porter\Terminal;
use Porter\Connection;
use Workerman\Worker;

require __DIR__ . '/bootstrap.php';

server()->onStart(function (Worker $worker) {
    Terminal::print("{text:darkGreen}Server started...");
});

server()->onConnected(function (Connection $connection, string $header) {
    Terminal::print('{text:darkGreen}User connected: ' . $connection->getRemoteAddress());
});

server()->onDisconnected(function (Connection $connection) {
    Terminal::print("{text:darkRed}User disconnected: " . $connection->getRemoteAddress());
});

server()->onError(function (Connection $connection, $code, $message) {
    Terminal::print("{bg:red}{text:white}Error occurred {$code}: {$message}");
});

server()->start();