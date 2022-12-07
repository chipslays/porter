<?php

use Porter\Connection;

server()->onDisconnected(function (Connection $connection) {
    cprint("{text:darkRed}User disconnected: " . $connection->getRemoteAddress());
});