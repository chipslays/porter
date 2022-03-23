<?php

use Porter\Channel;
use Porter\Server;
use Workerman\Worker;

if (!function_exists('server')) {
    /**
     * @return Server
     */
    function server(): Server {
        return Server::getInstance();
    }
}

if (!function_exists('worker')) {
    /**
     * @return Worker
     */
    function worker(): Worker {
        return Server::getInstance()->getWorker();
    }
}

if (!function_exists('channel')) {
    /**
     * @param string $id
     * @param string|array|null $key
     * @param mixed $default
     * @return Channel|mixed
     */
    function channel(string $id, string|array $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return Server::getInstance()->channels->get($id);
        }

        $channel = Server::getInstance()->channels->get($id);

        if (!$channel) {
            return null;
        }

        if (is_array($key)) {
            $channel->data->set($key[0], $key[1]);
            return null;
        }

        return $channel->data->get($key, $default);
    }
}

if (!function_exists('porterJsScripts')) {
    function porterJsScripts(): void {
        echo file_get_contents(__DIR__ . '/stubs/porter.js-script.html');
    }
}