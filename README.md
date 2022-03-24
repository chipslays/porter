# 🤵‍ Porter

Simple wrapper over Workerman Websockets.

# Installation

Via composer:

### **PHP**

Stable version:

```bash
composer require chipslays/porter ^1.x
```

Dev version:

```bash
composer require chipslays/porter dev-master
```

### Javascript

Via CDN:

```html
<script src="https://cdn.jsdelivr.net/gh/chipslays/porter/dist/porter.js"></script>
```

# Usage

### Server

Simplest ping-pong server.

```php
use Workerman\Worker;
use Porter\Events\Event;

require __DIR__ . '/vendor/autoload.php';

$worker = new Worker('websocket://0.0.0.0:3030');

server()->setWorker($worker);

server()->on('ping', function (Event $event) {
    $event->reply('pong');
});

server()->start();
```

### Client

Send `ping` event on established connection.

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Porter</title>
</head>
<body>
    <script src="https://cdn.jsdelivr.net/gh/chipslays/porter@1/dist/porter.js"></script>

    <script>
        const ws = new WebSocket('ws://localhost:3030');
        const client = new Porter(ws);

        client.connected = () => {
            client.event('ping');
        }

        client.on('pong', payload => {
            console.log(payload);
        });

        client.listen();
    </script>
</body>
</html>
```

See more in [examples](/examples) folder.

# Documentation

> **Notice:** Documentation in progress...

## Basics

### Local development

```php
use Workerman\Worker;

$worker = new Worker('websocket://0.0.0.0:3030');
```

### On server with SSL

```php
use Workerman\Worker;

$context = [
    // More see http://php.net/manual/zh/context.ssl.php
    'ssl' => [
        'local_cert' => '/path/to/cert.pem',
        'local_pk' => '/path/to/privkey.pem',
        'verify_peer' => false,
        // 'allow_self_signed' => true,
    ],
];
$worker = new Worker('websocket://0.0.0.0:3030', $context);
$worker->transport = 'ssl';
```

## `Server::class`

Can be used anywhere as function `server()` or `Server::getInstance()`.

```php
use Porter\Server;

$server = Server::getInstance();
$server->doSomething();
```

```php
server()->doSomething();
```


### `setWorker(Worker $worker): void`

```php
use Workerman\Worker;

$worker = new Worker('websocket://0.0.0.0:3030');
server()->setWorker($worker);
```

### `getWorker(): Worker`

```php
server()->getWorker();
```

### `addEvent(string $event): self`

```php
use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class Ping extends AbstractEvent
{
    public static string $id = 'ping';

    public function handle(TcpConnection $connection, Payload $payload, Server $server): void
    {
        $this->reply('pong');
    }
}

server()->addEvent(Ping::class);
```

### `on(string $eventId, callable $handler): void`

> **Notice:** `Event` class extends `AbstractEvent`.

```php
$server->on('ping', function (Event $event) {
    $event->reply('pong');
});
```

### `start(): void`

```php
server()->start();
```

### `onConnected(callable $handler): void`

Emitted when a socket connection is successfully established.

```php
use Porter\Terminal;
use Workerman\Connection\TcpConnection;

server()->onConnected(function (TcpConnection $connection) {
    Terminal::print('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());
});
```

### `onDisconnected(callable $handler): void`

Emitted when the other end of the socket sends a FIN packet.

```php
use Porter\Terminal;
use Workerman\Connection\TcpConnection;

server()->onDisconnected(function (TcpConnection $connection) {
    Terminal::print('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());
});
```

### `onError(callable $handler): void`

Emitted when an error occurs with connection.

```php
use Porter\Terminal;
use Workerman\Connection\TcpConnection;

server()->onError(function (TcpConnection $connection, $code, $message) {
    Terminal::print("{bg:red}{text:white}Error {$code} {$message}");
});
```

### `onStart(callable $handler): void`

Emitted when worker processes start.

```php
use Porter\Terminal;
use Workerman\Worker;

server()->onStart(function (Worker $worker) {
    // do something
});
```

### `onStop(callable $handler): void`

Emitted when worker processes stoped.

```php
use Porter\Terminal;
use Workerman\Worker;

server()->omStop(function (Worker $worker) {
    // do something
});
```

### `onReload(callable $handler): void`

Emitted when worker processes get reload signal.

```php
use Porter\Terminal;
use Workerman\Worker;

server()->onReload(function (Worker $worker) {
    // do something
});
```
