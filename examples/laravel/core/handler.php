<?php

use Porter\Events\Event;

use function porter\server;

/*
|--------------------------------------------------------------------------
| Events Handler
|--------------------------------------------------------------------------
|
| The place where you do your awesome things.
|
| Use functionally events bellow or make event classes
|   $ vendor/bin/porter make:event <path> <?type>
|   e.g. $ vendor/bin/porter make:event ./websocket/app/events/example.php "example event"
|
*/

server()->on('laravel version', function (Event $event) {
    $event->reply(data: [
        'version' => app()->version()],
    );
});
