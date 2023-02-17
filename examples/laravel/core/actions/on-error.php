<?php

use Porter\Connection;

use function porter\cprint;
use function porter\server;

server()->onError(function (Connection $connection, $code, $message) {
    cprint("{bg:red}{text:white}Error occurred {$code}: {$message}");
});