<?php

use Porter\Terminal;
use Workerman\Connection\TcpConnection;

require __DIR__ . '/bootstrap.php';

server()->onConnected(function (TcpConnection $connection) {
    Terminal::print('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());
});

server()->onDisconnected(function (TcpConnection $connection) {
    Terminal::print("{text:darkRed}Disconnected: " . $connection->getRemoteAddress());
});

server()->onError(function (TcpConnection $connection, $code, $message) {
    Terminal::print("{bg:red}{text:white}Error {$code} {$message}");
});

server()->onStart(function () {
    //
});

server()->start();