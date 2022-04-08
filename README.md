# 🤵‍ Porter: PHP Websocket Server

A simple wrapper over Workerman Websockets with channels and other stuff for PHP 8.1.

# Installation


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

Version|CDN
---|---
Uncompressed|`<script src="https://cdn.jsdelivr.net/gh/chipslays/porter/dist/porter.js"></script>`
Minified|`<script src="https://cdn.jsdelivr.net/gh/chipslays/porter/dist/porter.min.js"></script>`


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

```javascript
const ws = new WebSocket('ws://localhost:3030');
const client = new Porter(ws);

client.connected = () => {
    client.event('ping');
}

client.on('pong', payload => {
    console.log(payload);
});

client.listen();
```

> Don't forget include [PorterJS](#javascript) script via CDN.

See more in [examples](/examples) folder.

# Documentation

> **Notice:** Documentation in progress...

> **Notice:** You can view [source files](/src) for more information.

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
    // More see http://php.net/manual/en/context.ssl.php
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

## `Server`

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

server()->onStop(function (Worker $worker) {
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

### `to(TcpConnection $connection, string $event, array $data = []): bool|null`

Send event to connection.

```php
server()->to($connection, 'ping');
```

### `storage(): Storage`

Getter for Storage class.

```php
server()->storage();

server()->storage()->put('foo', 'bar');

$storage = server()->storage();
$storage->get('foo');

// can also be a get as propperty
server()->storage->put('foo', 'bar');
$storage = server()->storage;
```

### `channels(): Channels`

Getter for Channels class.

```php
server()->channels();

server()->channels()->create('secret channel');

$channels = server()->channels();
$channels->get('secret channel');

// can also be a get as propperty
server()->channels->create('secret channel');
$channels = server()->channels;
```

### `getConnection(int $connectionId): ?TcpConnection`

Get connection instance by id.

```php
$connection = server()->getConnection(1337);
server()->to($connection, 'welcome message', [
    'text' => 'Hello world!'
]);

// also can get like
$connection = server()->getWorker()->connections[1337] ?? null;
```

## `Channels`

This is a convenient division of connected connections into channels.

One connection can consist of an unlimited number of channels.

Channels also support broadcasting and their own storage.

Channel can be access like:

```php
// by method
server()->channels();

// by property
server()->channels;
```

### `create(string $id, array $data = []): Channel`

Create new channel.

```php
$channel = server()->channels->create('secret channel', [
    'foo' => 'bar',
]);

$channel->join($connection)->broadcast('welcome message', [
    'foo' => $channel->data->get('foo'),
]);
```

### `get(string $id): ?Channel`

Get a channel.

> Returns `NULL` if channel not exists.

```php
$channel = server()->channels()->get('secret channel');
$channel = server()->channels->get('secret channel');
```

### `all(): Channel[]`

Get array of channels (`Channel` instances).

```php
foreach (server()->channels->all() as $id => $channel) {
    echo count($channel->connections) . ' connection(s) in channel: ' . $id . PHP_EOL;
}
```

### `count(): int`

Get count of channels.

```php
$count = server()->channels->count();

echo "Total channels: {$count}";
```

### `delete(string $id): void`

Delete channel.

```php
server()->channels->delete('secret channel');
```

### `exists(string $id): bool`

Checks if given channel id exists already.

```php
$channelId = 'secret channel';
if (!server()->channels->exists($channelId)) {
    server()->channels->create($channelId);
}
```

### `join(string $id, TcpConnection|array $connections): Channel`

Join or create and join to channel.

```php
server()->channels->join($connection);
server()->channels->join([$connection1, $connection2, $connection3, ...]);
```

## `Channel`

### `join(TcpConnection|array $connections): self`

Join given connections to channel.

```php
$channel = server()->channels->get('secret channel');
$channel->join($connection);
$channel->join([$connection1, $connection2, $connection3, ...]);
```

### `leave(TcpConnection $connection): self`

Delete given connection from channel.

```php
$channel = server()->channels->get('secret channel');
$channel->leave($connection);
```

### `exists(TcpConnection $connection): bool`

Checks if given connection exists in channel.

```php
$channel = server()->channels->get('secret channel');
$channel->exists($connection);
```

### `broadcast(string $event, array $data = [], array $excepts = []): void`

Send an event to all connection on this channel.

> `TcpConnection[] $excepts` Connection instance or connection id.

```php
$channel = server()->channels->get('secret channel');
$channel->broadcast('welcome message', [
    'text' => 'Hello world',
]);
```

For example, you need to send to all participants in the room except yourself, or other connections.

```php
$channel->broadcast('welcome message', [
    'text' => 'Hello world',
], [$connection]);

$channel->broadcast('welcome message', [
    'text' => 'Hello world',
], [$connection1, $connection2, ...]);
```

### `destroy(): void`

Delete this channel from channels.

```php
$channel = server()->channels->get('secret channel');
$channel->desstoy();

// now if use $channel, you get an error
$channel->data->get('foo');
```

## Properties

### `$channel->connections`

A array of connections in this channel. Key is a `id` of connection, and value is a instance of connection `TcpConnection`.

```php
$channel = server()->channels->get('secret channel');

foreach($channel->connections, as $connection) {
    $connection->lastMessageAt = time();
}
```

```php
$channel = server()->channels->get('secret channel');

$connection = $channel->connections[1337];
```

### `$channel->data`

Data is a simple implement of box for storage your data.

Data is a object of powerful [chipslays/collection](https://github.com/chipslays/collection).

See [documentation](https://github.com/chipslays/collection) for more information how to manipulate this data.

> **NOTICE:** All this data will be deleted when the server is restarted.

Couple of simple-short examples:

```php
$channel->data->set('foo');
$channel->data->get('foo', 'default value');
$channel->data->has('foo', 'default value');

$channel->data['foo'];
$channel->data['foo'] ?? 'default value';
isset($channel->data['foo']);

// see more examples here: https://github.com/chipslays/collection
```


## `Payload`

The payload is the object that came from the client.

### `get(string $key, mixed $default = null): mixed`

Get value from data.

```php
$payload->get('foo', 'default value');

// can also use like:
$payload->data->get('foo', 'default value');
$payload->data['foo'] ?? 'default value';
```

## Properties

### `$payload->eventId`

Is a id of event, for example, `welcome message`.

```php
$payload->eventId; // string
```

### `$payload->timestamp`

A timestamp when event was came.

```php
$payload->timestamp; // int
```

### `$payload->timestamp`

An object of values passed from the client.

Object of [chipslays/collection](https://github.com/chipslays/collection).

See [documentation](https://github.com/chipslays/collection) for more information how to manipulate this data.

```php
$payload->data; // Collection
```







*More to come later...*

# Credits

* [`Workerman`](https://github.com/walkor/workerman) by [walkor](https://github.com/walkor)

# License
MIT
