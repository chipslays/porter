<?php

use Porter\Connection;

server()->onConnected(function (Connection $connection) {
    cprint('{text:darkGreen}User connected: ' . $connection->getRemoteAddress());
});