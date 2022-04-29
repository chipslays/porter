# 🤵‍ Porter: PHP Websocket Server/Client

A simple wrapper over Workerman Websockets with channels and other stuff for PHP 8.1.

# Installation

### **PHP**

Via composer:

```bash
composer require chipslays/porter ^1.x
```


### Javascript

Via jsDelivr CDN:

```html
<script src="https://cdn.jsdelivr.net/gh/chipslays/porter@latest/dist/porter.min.js"></script>
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
$server->on(...);
```

```php
server()->on(...);
```


### `setWorker(Worker $worker): void`

Set worker instance.

```php
use Workerman\Worker;

$worker = new Worker('websocket://0.0.0.0:3030');
server()->setWorker($worker);
```

### `getWorker(): Worker`

Get worker instance.

```php
server()->getWorker();
```

### `addEvent(string $event): self`

Add event class handler.

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



> **Notice:** `Event $event` class extends and have all methods & properties of `AbstractEvent`.

```php
$server->on('ping', function (Event $event) {
    $event->reply('pong');
});
```

### `start(): void`

Start server.

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

> **NOTICE:** On disconnect client connection will leave of all the channels where he was.

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
    //
});
```

### `onStop(callable $handler): void`

Emitted when worker processes stoped.

```php
use Porter\Terminal;
use Workerman\Worker;

server()->onStop(function (Worker $worker) {
    //
});
```

### `onReload(callable $handler): void`

Emitted when worker processes get reload signal.

```php
use Porter\Terminal;
use Workerman\Worker;

server()->onReload(function (Worker $worker) {
    //
});
```

### `onRaw(callable $handler): void`

Handle non event messages (raw data).

```php
server()->onRaw(function (string $payload, TcpConnection $connection) {
    if ($payload == 'ping') {
        $connection->send('pong');
    }
});
```

### `to(TcpConnection $connection, string $event, array $data = []): ?bool`

Send event to connection.

```php
server()->to($connection, 'ping');
```

### `broadcast(string $event, array $data = [], array $excepts = []): void`

Send event to all connections.

Yes, to **all connections** on server.

```php
server()->broadcast('chat message', [
    'nickname' => 'John Doe',
    'message' => 'Hello World!',
]);
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

### `validator(): Validator`

Create validator instance.

See [documenation & examples](https://respect-validation.readthedocs.io/en/latest) how to use.

```php
$v = server()->validator();

if ($v->email()->validate('john.doe@example.com')) {
    //
}

// available as helper
if (validator()->contains('example.com')->validate('john.doe@example.com')) {
    //
}
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

### Lifehack for `Channel`

You can add channel  to current user as property to `$connection` instance and get it anywhere.

```php
$channel = channel('secret channel');
$connection->channel = &$channel;
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

### `is(string|array $rule, string $property): bool`

Validate payload data.

See [documenation & examples](https://respect-validation.readthedocs.io/en/latest) how to use.

```php
$payload->is('StringType', 'username'); // return true if username is string
$payload->is(['contains', 'john'], 'username'); // return true if $payload->data['username'] contains 'john'
```

## Properties

### `$payload->eventId`

Is a id of event, for example, `welcome message`.

```php
$payload->eventId; // string
```

### `$payload->timestamp`

A timestamp when event was came (server time and timezone).

```php
$payload->timestamp; // int
```

### `$payload->data`

An object of values passed from the client.

Object of [chipslays/collection](https://github.com/chipslays/collection).

See [documentation](https://github.com/chipslays/collection) for more information how to manipulate this data.

```php
$payload->data; // Collection

$payload->data->set('foo');
$payload->data->get('foo', 'default value');
$payload->data->has('foo', 'default value');

$payload->data['foo'];
$payload->data['foo'] ?? 'default value';
isset($payload->data['foo']);

// see more examples here: https://github.com/chipslays/collection
```

### `$payload->rules` [protected]

Auto validate payload data on incoming event.

Available only in events as `class`.

```php
use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class HelloToEvent extends AbstractEvent
{
    public static string $eventId = 'hello to';

    protected array $rules = [
        'username' => ['stringType', ['length', [3, 18]]],
    ];

    public function handle(TcpConnection $connection, Payload $payload, Server $server): void
    {
        if ($this->hasErrors()) {
            $this->reply('bad request', ['errors' => $this->errors]);
            return;
        }

        $username = $this->payload->data['username'];
        $this->reply(data: ['message' => "Hello, {$username}!"]);
    }
}
```

## `Events`

Events can be as a separate class or as an anonymous function.

### Event class

Basic ping-pong example:

```php
use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Workerman\Connection\TcpConnection;

class Ping extends AbstractEvent
{
    public static string $eventId = 'ping';

    public function handle(TcpConnection $connection, Payload $payload, Server $server): void
    {
        $this->reply('pong');
    }
}

// and next you need add (register) this class to events:
server()->addEvent(Ping::class);
```

> **NOTICE:** The event class must have a `handle()` method.
>
> This method handles the event. You can also create other methods.

### `AbstractEvent`

#### Properties

Each child class get following properties:

* `TcpConnection $connection` - from whom the event came;
* `Payload $payload` - contain data from client;
* `Server $server` - server instance;

#### Methods

#### `to(TcpConnection $connection, string $event, array $data = []): ?bool`

Send event to connection.

```php
$this->to($connection, 'ping');
```

#### `reply(string $event, array $data = []): ?bool`

Reply event to incoming connection.

```php
$this->reply('ping');

// analog for:
$this->to($this->connection, 'ping');
```

To reply with the current `eventId`, pass only the `$data` parameter.

**On front-end:**
```javascript
client.event('hello to', {username: 'John Doe'}, payload => {
    console.log(payload.data.message); // Hello, John Doe!
});
```

**On back-end:**
```php
$username = $this->payload->data['username'];
$this->reply(data: ['message' => "Hello, {$username}!"]);
```

#### `raw(string $string): bool|null`

Send raw data to connection. Not a event object.

```php
$this->raw('ping');

// now client will receive just a 'ping', not event object.
```

#### `broadcast(string $event, array $data = [], array $excepts = []): void`

Send event to all connections.

Yes, to **all connections** on server.

```php
$this->broadcast('chat message', [
    'nickname' => 'John Doe',
    'message' => 'Hello World!',
]);
```

Send event to all except for the connection from which the event came.

```php
$this->broadcast('user join', [
    'text' => 'New user joined to chat.',
], [$this->connection]);
```

### `hasErrors(): bool`

Returns `true` if has errors on validate payload data.

```php
if ($this->hasErrors()) {
    $this->reply('bad request', ['errors' => $this->errors]);
    return;
}
```

```textplain
// $this->errors contains:

^ array:1 [
  "username" => array:1 [
    "length" => "username failed validation: length"
  ]
]
```

#### Magic properties & methods.

If client pass in data `channelId` with channel id or `targetId` with id of connection, we got a magic properties and methods.

```php
// this is a object of Channel, getted by `channelId` from client.
$this->channel;
$this->channel();

$this->channel->broadcast('new message', [
    'text' => $this->payload->get('text'),
    'from' => $this->connection->nickname,
]);
```

```php
// this is a object of Channel, getted by `channelId` from client.
$this->target;
$this->target();

$this->to($this->target, 'new message', [
    'text' => $this->payload->get('text'),
    'from' => $this->connection->nickname,
]);
```


### `Anonymous function`

In anonymous function instead of `$this`, use `$event`.

```php
use Porter\Events\Event;

server()->on('new message', function (Event $event) {
    // $event has all the same property && methods as in the example above

    $event->to($event->target, 'new message', [
        'text' => $this->payload->get('text'),
        'from' => $this->connection->nickname,
    ]);

    $event->channel->broadcast('new message', [
        'text' => $this->payload->get('text'),
        'from' => $this->connection->nickname,
    ]);
});
```

## `TcpConnection $connection`

It is a global object, changing in one place, it will contain the changed data in another place.

This object has already predefined properties:

See all `$connection` methods [here](https://doc.hotexamples.com/class/workerman.connection/TcpConnection).

You can set different properties, functions to this object.

```php
$connection->firstName = 'John';
$connection->lastName = 'Doe';
$connection->getFullName = fn () => $connection->firstName . ' ' . $connection->lastName;

call_user_func($connection->getFullname); // John Doe
```

### Custom property `channels`

```php
$connection->channels; // object of Porter\Connection\Channels
```

#### List of methods `Porter\Connection\Channels`

```php
/**
 * Get connection channels.
 *
 * @return Channel[]
 */
public function all(): array
```

```php
/**
 * Get channels count.
 *
 * @return integer
 */
public function count(): int
```

```php
/**
 * Method for when connection join to channel should detach channel id from connection.
 *
 * @param string $channelId
 * @return void
 */
public function delete(string $channelId): void
```

```php
/**
 * Leave all channels for this connection.
 *
 * @return void
 */
public function leaveAll(): void
```

> **NOTICE:** On disconnect client connection will leave of all the channels where he was.

```php
/**
 * When connection join to channel should attach channel id to connection.
 *
 * You don't need to use this method, it will automatically fire inside the class.
 *
 * @param string $channelId
 * @return void
 */
public function add(string $channelId): void
```







## `Client`

Simple implementation of client.

See basic example of client [here](/examples/client-php/client.php).

#### `__construct(string $host, array $context = [])`

Set worker.

```php
$client = new Client('ws://localhost:3030');
$client = new Client('wss://example.com:3030');
```

#### `setWorker(Worker $worker): void`

Set worker.

> **NOTICE:** Worker instance auto init in constructor. Use this method if you need to define worker specific settings.

#### `getWorker(): Worker`

Get worker.

#### `event(string $eventId, array $data = []): ?bool`

Send event to server.

```php
$client->on('ping', function (AsyncTcpConnection $connection, Payload $payload, Client $client) {
    $client->event('pong', ['time' => time()]);
});
```

#### `raw(string $payload): ?bool`

Send raw payload to server.

```php
$client->raw('simple message');
```

#### `onConnected(callable $handler): void`

Emitted when a socket connection is successfully established.

```php
$client->onConnected(function (AsynTcpConnection $connection) {
    //
});
```

#### `onDisconnected(callable $handler): void`

Emitted when the server sends a FIN packet.

```php
$client->onDisconnected(function (AsynTcpConnection $connection) {
    //
});
```

#### `onError(callable $handler): void`

Emitted when an error occurs with connection.

```php
$client->onError(function (AsyncTcpConnection $connection, $code, $message) {
    //
});
```

#### `onRaw(callable $handler): void`

Handle non event messages (raw data).

```php
$client->onRaw(function (string $payload, AsyncTcpConnection $connection) {
    if ($payload == 'ping') {
        $connection->send('pong');
    }
});
```

#### `on(string $eventId, callable $handler): void`

Event handler as callable.

```php
$client->on('pong', function (AsyncTcpConnection $connection, Payload $payload, Client $client) {
    //
});
```

#### `listen(): void`

Connect to server and listen.

```php
$client->listen();
```





## `Storage`

Storage is a part of server, all data stored in flat files.

To get started you need set a `$path` where files will be stored.

```php
server()->storage->path = __DIR__ . '/Storage/storage.hub';
```

You can get access to storage like property or method:

```php
server()->storage;
server()->storage();
```

> **NOTICE:** Set path only after you set worker instance (`server()->setWorker($worker)`).

> **NOTICE:** If path not setting, data will be stored in RAM. After server restart you lose your data.

### `put(string $key, mixed $value): void`

```php
server()->storage->put('foo', 'bar');
```

### `get(string $key, mixed $default = null): mixed`

```php
server()->storage->get('foo', 'default value'); // foo
server()->storage->get('baz', 'default value'); // default value
```

### `has(string $key): bool`

```php
server()->storage->has('foo'); // true
server()->storage->has('baz'); // false
```

### `delete(): bool`

Remove storage file from disk.

```php
server()->storage->delete();
```

### `getPath(): ?string`

Returns `string` if path not empty.

```php
server()->storage->getPath();
```

### Storage as standalone

You can use `Storage` class as standalone anywhere.

```php
use Porter\Storage;

$storage1 = new Storage(__DIR__ . '/storage/storage1.hub');
$storage2 = new Storage(__DIR__ . '/storage/storage2.hub');
$storage3 = new Storage(__DIR__ . '/storage/storage3.hub');
```

## Helpers (functions)

### `server(): Server`

```php
server()->on(...);

// will be like:
use Porter\Server;
Server::getInstance()->on(...);
```

### `worker(): Worker`

```php
worker()->connections;

// will be like:
use Porter\Server;
Server::getInstance()->getWorker()->connections;
```

### `channel(string $id, string|array $key = null, mixed $default = null): mixed`

```php
$channel = channel('secret channel'); // get channel instance
$channel = server()->channels->get('secret channel');

channel('secret channel', ['foo' => 'bar']); // set data for given channel (by id)
server()->channels->set('secret channel')->set('foo', 'bar');

channel('secret channel', 'foo', 'default value'); // get data from channel (by id)
server()->channels->get('secret channel')->data->get('foo', 'default value');
```

## Mappable methods

You can extend the class and map your own methods on the fly..

Basic method:
```php
server()->map('sum', fn(...$args) => array_sum($args));
echo server()->sum(1000, 300, 30, 5, 2); // 1337
echo server()->sum(1000, 300, 30, 5, 3); // 1338
```

As singletone method:
```php
server()->mapOnce('timestamp', fn() => time());
echo server()->timestamp(); // e.g. 1234567890
sleep(1);
echo server()->timestamp(); // e.g. 1234567890
```

# Front-end

There is also a [small class](#javascript) for working with websockets on the client side.

```javascript
const ws = new WebSocket('ws://localhost:3031'); // on local dev
const ws = new WebSocket('wss://example.com:3031'); // on prod server
const client = new Porter(ws);

// on client connected to server
client.connected = () => {
    //
}

// on client disconected from server
client.disconnected = () => {
    //
}

// on error
client.error = () => {
    //
}

// close connection
client.close();

// event handler
client.on('ping', payload => {
    // available property
    payload.eventId;
    payload.timestamp;
    payload.data;

    console.log(payload.data.foo) // bar
});

// send event
client.event('pong', {
    foo: 'bar',
});

// chain methods
client.event('ping').on('pong', payload => console.log(payload.timestamp));

// support short variants:
client.event('get online users', {}, payload => {
    console.log(payload.eventId); // get online users
    console.log(payload.data.online); // e.g. 5
});

// pass channelId and targetId for magic properties on back-end server
client.event('magical properties', {
    channelId: 'secret channel',
    targetId: 1337,
});

// start listen
client.listen();
```

# Credits

* [chipslays](https://github.com/chipslays)
* [`Workerman`](https://github.com/walkor/workerman) by [walkor](https://github.com/walkor)

# License
MIT
