<?php

use Porter\Connection;

server()->onError(function (Connection $connection, $code, $message) {
    cprint("{bg:red}{text:white}Error occurred {$code}: {$message}");
});