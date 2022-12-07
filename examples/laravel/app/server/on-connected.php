<?php

use Porter\Connection;

server()->onConnected(function (Connection $connection, string $header) {
    cprint('{text:darkGreen}User connected: ' . $connection->getRemoteAddress());
});