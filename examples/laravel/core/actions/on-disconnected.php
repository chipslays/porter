<?php

use Porter\Connection;

use function porter\cprint;
use function porter\server;

server()->onDisconnected(function (Connection $connection) {
    cprint("{text:darkRed}User disconnected: " . $connection->getRemoteAddress());
});