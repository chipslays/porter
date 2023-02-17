<?php

use Porter\Connection;

use function porter\cprint;
use function porter\server;

server()->onConnected(function (Connection $connection) {
    cprint('{text:darkGreen}User connected: ' . $connection->getRemoteAddress());
});