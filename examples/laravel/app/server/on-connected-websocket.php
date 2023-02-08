<?php

use Porter\Connection;

use function porter\server;

server()->onWebsocketConnected(function (Connection $connection, string $header) {
    //
});