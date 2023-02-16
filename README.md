# Porter 🤵‍
![](https://img.shields.io/github/license/chipslays/porter?color=blue)
![](https://img.shields.io/packagist/dt/chipslays/porter)

A simple PHP 8 websocket server and client wrapper over Workerman with events, channels and other stuff, can say this is a Socket IO alternative for PHP.

![](/.github/images/porter.png)

> **Note**
>
> Latest version 1.2 is production ready, maintenance only small features, fix bugs  and has **no breaking changes** updates.

# 🧰 Installation

1. Install Porter via Composer:

```bash
composer require chipslays/porter
```

2. Put javascript code in views:

```html
<script src="https://cdn.jsdelivr.net/gh/chipslays/porter@latest/dist/porter.min.js"></script>
```

3. All done.

> Laravel integration can be found [here](/examples/laravel/README.md).

# 👨‍💻 Usage

### Server (PHP)

Simplest ping-pong server.

```php
use Porter\Events\Event;

require __DIR__ . '/vendor/autoload.php';

server()->create('0.0.0.0:3737');

server()->on('ping', function (Event $event) {
    $event->reply('pong');
});

server()->start();
```

Run server.

```bash
php server.php start
```

Or run server in background as daemon process.

```bash
php server.php start -d
```

<details>
  <summary>List of all available commands</summary>

`php server.php start`

`php server.php start -d`

`php server.php status`

`php server.php status -d`

`php server.php connections`

`php server.php stop`

`php server.php stop -g`

`php server.php restart`

`php server.php reload`

`php server.php reload -g`
</details>

### Client (Javascript)

Send `ping` event on established connection.

```html
<script src="https://cdn.jsdelivr.net/gh/chipslays/porter@latest/dist/porter.min.js"></script>

<script>
    const client = new Porter(`ws://${location.hostname}:3737`);

    client.connected = () => {
        client.send('ping');
    }

    client.on('pong', payload => {
        console.log(payload);
    });

    client.listen();
</script>
```

# 💡 Examples

Examples can be found [here](/examples).

# 📚 Documentation

> **NOTE:** The documentation may not contain the latest updates or may be out of date in places. See examples, code and comments on methods. The code is well documented.

## Basics

### Local development

```php
use Workerman\Worker;

$worker = new Worker('websocket://0.0.0.0:3737');
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
$worker = new Worker('websocket://0.0.0.0:3737', $context);
$worker->transport = 'ssl';
```

## 🔹 `Server`

Can be used anywhere as function `server()` or `Server::getInstance()`.

```php
use Porter\Server;

$server = Server::getInstance();
$server->on(...);
```

```php
server()->on(...);
```

#### `boot(Worker $worker): self`

Booting websocket server. It method init all needle classes inside.

> Use this method instead of constructor.

```php
$server = Server::getInstance();
$server->boot($worker);

// by helper function
server()->boot($worker);
```

#### `setWorker(Worker $worker): void`

Set worker instance.

```php
use Workerman\Worker;

$worker = new Worker('websocket://0.0.0.0:3737');
server()->boot($worker); // booting server

$worker = new Worker('websocket://0.0.0.0:3737');
$worker->... // configure new worker

// change worker in already booted server
server()->setWorker($worker);
```

#### `setWorker(Worker $worker): void`

Set worker instance.

```php
use Workerman\Worker;

$worker = new Worker('websocket://0.0.0.0:3737');
server()->setWorker($worker);
```

#### `getWorker(): Worker`

Get worker instance.

```php
server()->getWorker();
```

#### `addEvent(AbstractEvent|string $event): self`

Add event class handler.

```php
use Porter\Server;
use Porter\Payload;
use Porter\Connection;
use Porter\Events\AbstractEvent;

class Ping extends AbstractEvent
{
    public static string $id = 'ping';

    public function handle(Connection $connection, Payload $payload, Server $server): void
    {
        $this->reply('pong');
    }
}

server()->addEvent(Ping::class);
```

#### `autoloadEvents(string $path, string|array $masks = ['*.php', '**/*.php']): void`

Autoload all events inside passed path.

> Note: Use it instead manual add events by `addEvent` method.

```php
server()->autoloadEvents(__DIR__ . '/Events');
```

#### `on(string $type, callable $handler): void`



> **Note** 
>
> `Event $event` class extends and have all methods & properties of `AbstractEvent`.

```php
$server->on('ping', function (Event $event) {
    $event->reply('pong');
});
```

#### `start(): void`

Start server.

```php
server()->start();
```

#### `onConnected(callable $handler): void`

Emitted when a socket connection is successfully established.

> In this method available vars: `$_GET`, `$_COOKIE`, `$_SERVER`.

```php
use Porter\Terminal;
use Porter\Connection;

server()->onConnected(function (Connection $connection, string $header) {
    Terminal::print('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());

    // Here also available vars: $_GET, $_COOKIE, $_SERVER.
    Terminal::print("Query from client: {text:darkYellow}foo={$_GET['foo']}");
});
```

#### `onDisconnected(callable $handler): void`

Emitted when the other end of the socket sends a FIN packet.

> **NOTICE:** On disconnect client connection will leave of all the channels where he was.

```php
use Porter\Terminal;
use Porter\Connection;

server()->onDisconnected(function (Connection $connection) {
    Terminal::print('{text:darkGreen}Connected: ' . $connection->getRemoteAddress());
});
```

#### `onError(callable $handler): void`

Emitted when an error occurs with connection.

```php
use Porter\Terminal;
use Porter\Connection;

server()->onError(function (Connection $connection, $code, $message) {
    Terminal::print("{bg:red}{text:white}Error {$code} {$message}");
});
```

#### `onStart(callable $handler): void`

Emitted when worker processes start.

```php
use Porter\Terminal;
use Workerman\Worker;

server()->onStart(function (Worker $worker) {
    //
});
```

#### `onStop(callable $handler): void`

Emitted when worker processes stoped.

```php
use Porter\Terminal;
use Workerman\Worker;

server()->onStop(function (Worker $worker) {
    //
});
```

#### `onReload(callable $handler): void`

Emitted when worker processes get reload signal.

```php
use Porter\Terminal;
use Workerman\Worker;

server()->onReload(function (Worker $worker) {
    //
});
```

#### `onRaw(callable $handler): void`

Handle non event messages (raw data).

```php
server()->onRaw(function (string $payload, Connection $connection) {
    if ($payload == 'ping') {
        $connection->send('pong');
    }
});
```

#### `to(TcpConnection|Connection|array $connection, string $event, array $data = []): self`

Send event to connection.

```php
server()->to($connection, 'ping');
```

#### `broadcast(string $event, array $data = [], array $excepts = []): void`

Send event to all connections.

Yes, to **all connections** on server.

```php
server()->broadcast('chat message', [
    'nickname' => 'John Doe',
    'message' => 'Hello World!',
]);
```

#### `storage(): Storage`

Getter for Storage class.

```php
server()->storage();

server()->storage()->put('foo', 'bar');

$storage = server()->storage();
$storage->get('foo');

// can also be a get as propperty
server()->storage()->put('foo', 'bar');
$storage = server()->storage;
```

#### `channels(): Channels`

Getter for Channels class.

```php
server()->channels();

server()->channels()->create('secret channel');

$channels = server()->channels();
$channels->get('secret channel');
```

#### `connection(int $connectionId): ?Connection`

Get connection instance by id.

```php
$connection = server()->connection(1);
server()->to($connection, 'welcome message', [
    'text' => 'Hello world!'
]);

// also can get like
$connection = server()->getWorker()->connections[1337] ?? null;
```

#### `connections(): Collection[]`

Get collection of all connections on server.

```php
$connections = server()->connections();
server()->broadcast('update users count', ['count' => $connections->count()]);

// also can get like
$connections = server()->getWorker()->connections;
```

#### `validator(): Validator`

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

## 🔹 `Channels`

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

#### `create(string $id, array $data = []): Channel`

Create new channel.

```php
$channel = server()->channels()->create('secret channel', [
    'foo' => 'bar',
]);

$channel->join($connection)->broadcast('welcome message', [
    'foo' => $channel->data->get('foo'),
]);
```

#### `get(string $id): ?Channel`

Get a channel.

> Returns `NULL` if channel not exists.

```php
$channel = server()->channels()->get('secret channel');
```

#### `all(): Channel[]`

Get array of channels (`Channel` instances).

```php
foreach (server()->channels()->all() as $id => $channel) {
    echo $channel->connections()->count() . ' connection(s) in channel: ' . $id . PHP_EOL;
}
```

#### `count(): int`

Get count of channels.

```php
$count = server()->channels()->count();

echo "Total channels: {$count}";
```

#### `delete(string $id): void`

Delete channel.

```php
server()->channels()->delete('secret channel');
```

#### `exists(string $id): bool`

Checks if given channel id exists already.

```php
$channelId = 'secret channel';
if (!server()->channels()->exists($channelId)) {
    server()->channels()->create($channelId);
}
```

#### `join(string $id, Connection|Connection[]|int[] $connections): Channel`

Join or create and join to channel.

```php
server()->channels()->join($connection);
server()->channels()->join([$connection1, $connection2, $connection3, ...]);
```

## 🔹 `Channel`

#### `join(TcpConnection|Connection|array $connections): self`

Join given connections to channel.

```php
$channel = server()->channel('secret channel');
$channel->join($connection);
$channel->join([$connection1, $connection2, $connection3, ...]);
```

#### `leave(TcpConnection|Connection $connection): self`

Remove given connection from channel.

```php
$channel = server()->channel('secret channel');
$channel->leave($connection);
```

#### `exists(TcpConnection|Connection|int $connection): bool`

Checks if given connection exists in channel.

```php
$channel = server()->channel('secret channel');
$channel->exists($connection);
```

#### `connections(): Connections`

A array of connections in this channel. Key is a `id` of connection, and value is a instance of connection `Connection`.

```php
$channel = server()->channel('secret channel');

foreach($channel->connections()->all()) as $connection) {
    $connection->lastMessageAt = time();
}
```

```php
$channel = server()->channel('secret channel');

$connection = $channel->connections()->get([1337]); // get connection with 1337 id
```

#### `broadcast(string $event, array $data = [], array $excepts = []): void`

Send an event to all connection on this channel.

> `TcpConnection[]|Connection[]|int[] $excepts` Connection instance or connection id.

```php
$channel = server()->channel('secret channel');
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

#### `destroy(): void`

Delete this channel from channels.

```php
$channel = server()->channel('secret channel');
$channel->destroy();

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

#### `$channel->data`

Data is a simple implement of box for storage your data.

Data is a object of powerful [chipslays/collection](https://github.com/chipslays/collection).

See [documentation](https://github.com/chipslays/collection) for more information how to manipulate this data.

> **NOTICE:** All this data will be deleted when the server is restarted.

Two of simple-short examples:

```php
$channel->data->set('foo');
$channel->data->get('foo', 'default value');
$channel->data->has('foo', 'default value');

$channel->data['foo'];
$channel->data['foo'] ?? 'default value';
isset($channel->data['foo']);

// see more examples here: https://github.com/chipslays/collection
```


## 🔹 `Payload`

The payload is the object that came from the client.

#### `payload(string $key, mixed $default = null): mixed`

Get value from data.

```php
$payload->get('foo', 'default value');

// can also use like:
$payload->data->get('foo', 'default value');
$payload->data['foo'] ?? 'default value';
```

#### `is(string|array $rule, string $key): bool`

Validate payload data.

See [documenation & examples](https://respect-validation.readthedocs.io/en/latest) how to use.

```php
$payload->is('StringType', 'username'); // return true if username is string
$payload->is(['contains', 'john'], 'username'); // return true if $payload->data['username'] contains 'john'
```

## Properties

#### `$payload->type`

Is a id of event, for example, `welcome message`.

```php
$payload->type; // string
```

#### `$payload->data`

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

#### `$payload->rules` [protected]

Auto validate payload data on incoming event.

Available only in events as `class`.

```php
use Porter\Server;
use Porter\Payload;
use Porter\Connection;
use Porter\Events\AbstractEvent;

return new class extends AbstractEvent
{
    public static string $type = 'hello to';

    protected array $rules = [
        'username' => ['stringType', ['length', [3, 18]]],
    ];

    public function handle(Connection $connection, Payload $payload, Server $server): void
    {
        if (!$this->validate()) {
            $this->reply('bad request', ['errors' => $this->errors]);
            return;
        }

        $username = $this->payload->data['username'];
        $this->reply(data: ['message' => "Hello, {$username}!"]);
    }
};
```

## 🔹 `Events`

Events can be as a separate class or as an anonymous function.

### Event class

Basic ping-pong example:

```php
use Porter\Server;
use Porter\Payload;
use Porter\Events\AbstractEvent;
use Porter\Connection;

class Ping extends AbstractEvent
{
    public static string $type = 'ping';

    public function handle(Connection $connection, Payload $payload, Server $server): void
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

#### `AbstractEvent`

#### Properties

Each child class get following properties:

* `Connection $connection` - from whom the event came;
* `Payload $payload` - contain data from client;
* `Server $server` - server instance;
* `Collection $data` - short cut for payload data (as &link).;

#### Magic properties & methods.

If client pass in data `channel_i_d` with channel id or `target_id` with id of connection, we got a magic properties and methods.

```php
// this is a object of Channel, getted by `channel_id` from client.
$this->channel;
$this->channel();

$this->channel()->broadcast('new message', [
    'text' => $this->payload->get('text'),
    'from' => $this->connection->nickname,
]);
```

```php
// this is a object of Channel, getted by `target_id` from client.
$this->target;
$this->target();

$this->to($this->target, 'new message', [
    'text' => $this->payload->get('text'),
    'from' => $this->connection->nickname,
]);
```

#### Methods

##### `to(TcpConnection|Connection|array $connection, string $event, array $data = []): self`

Send event to connection.

```php
$this->to($connection, 'ping');
```

##### `reply(string $event, array $data = []): ?bool`

Reply event to incoming connection.

```php
$this->reply('ping');

// analog for:
$this->to($this->connection, 'ping');
```

To reply with the current `type`, pass only the `$data` parameter.

**On front-end:**
```javascript
client.send('hello to', {username: 'John Doe'}, payload => {
    console.log(payload.data.message); // Hello, John Doe!
});
```

**On back-end:**
```php
$username = $this->payload->data['username'];
$this->reply(data: ['message' => "Hello, {$username}!"]);
```

##### `raw(string $string): bool|null`

Send raw data to connection. Not a event object.

```php
$this->raw('ping');

// now client will receive just a 'ping', not event object.
```

##### `broadcast(string $event, array $data = [], TcpConnection|Connection|array $excepts = []): void`

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

#### `validate(): bool`

Validate payload data.

Pass custom rules. Default use $rules class attribute.

Returns `false` if has errors.

```php
if (!$this->validate()) {
    return $this->reply(/** ... */);
}
```

#### `hasErrors(): bool`

Returns `true` if has errors on validate payload data.

```php
if ($this->hasErrors()) {
    return $this->reply('bad request', ['errors' => $this->errors]);
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

#### `payload(string $key, mixed $default = null): mixed`

Yet another short cut for payload data.

```php
public function handle(Connection $connection, Payload $payload, Server $server)
{
    $this->get('nickname');

    // as property
    $this->data['nickname'];
    $this->data->get('nickname');

    // form payload instance
    $payload->data['nickname'];
    $payload->data->get('nickname');

    $this->payload->data['nickname'];
    $this->payload->data->get('nickname');
}
```




#### `Anonymous function`

In anonymous function instead of `$this`, use `$event`.

```php
use Porter\Events\Event;

server()->on('new message', function (Event $event) {
    // $event has all the same property && methods as in the example above

    $event->to($event->target, 'new message', [
        'text' => $this->payload->get('text'),
        'from' => $this->connection->nickname,
    ]);

    $event->channel()->broadcast('new message', [
        'text' => $this->payload->get('text'),
        'from' => $this->connection->nickname,
    ]);
});
```

## 🔹 `TcpConnection|Connection $connection`

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
 * @return int
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







## 🔹 `Client (PHP)`

Simple implementation of client.

See basic example of client [here](/examples/client-php/client.php).

##### `__construct(string $host, array $context = [])`

Create client.

```php
$client = new Client('ws://localhost:3737');
$client = new Client('wss://example.com:3737');
```

##### `setWorker(Worker $worker): void`

Set worker.

> **NOTICE:** Worker instance auto init in constructor. Use this method if you need to define worker with specific settings.

##### `getWorker(): Worker`

Get worker.

##### `send(string $type, array $data = []): ?bool`

Send event to server.

```php
$client->on('ping', function (AsyncTcpConnection $connection, Payload $payload, Client $client) {
    $client->send('pong', ['time' => time()]);
});
```

##### `raw(string $payload): ?bool`

Send raw payload to server.

```php
$client->raw('simple message');
```

##### `onConnected(callable $handler): void`

Emitted when a socket connection is successfully established.

```php
$client->onConnected(function (AsynTcpConnection $connection) {
    //
});
```

##### `onDisconnected(callable $handler): void`

Emitted when the server sends a FIN packet.

```php
$client->onDisconnected(function (AsynTcpConnection $connection) {
    //
});
```

##### `onError(callable $handler): void`

Emitted when an error occurs with connection.

```php
$client->onError(function (AsyncTcpConnection $connection, $code, $message) {
    //
});
```

##### `onRaw(callable $handler): void`

Handle non event messages (raw data).

```php
$client->onRaw(function (string $payload, AsyncTcpConnection $connection) {
    if ($payload == 'ping') {
        $connection->send('pong');
    }
});
```

##### `on(string $type, callable $handler): void`

Event handler as callable.

```php
$client->on('pong', function (AsyncTcpConnection $connection, Payload $payload, Client $client) {
    //
});
```

##### `listen(): void`

Connect to server and listen.

```php
$client->listen();
```





## 🔹 `Storage`

Storage is a part of server, all data stored in flat files.

To get started you need set a path where files will be stored.

```php
server()->storage()->load(__DIR__ . '/server-storage.data'); // you can use any filename
```

You can get access to storage like property or method:

```php
server()->storage();
```

> **NOTICE:** Set path only after if you booting server by (`server()->boot($worker)` method, `Storage::class` can use anywhere and before booting server.

> **WARNING:** If you not provide path or an incorrect path, data will be stored in RAM. After server restart you lose your data.

#### `Storage::class`

```php
// as standalone use without server
$store1 = new Porter\Storage(__DIR__ . '/path/to/file1');
$store2 = new Porter\Storage(__DIR__ . '/path/to/file2');
$store3 = new Porter\Storage(__DIR__ . '/path/to/file3');
```

#### `load(?string $path = null): self`

```php
server()->storage()->load(__DIR__ . '/path/to/file'); // you can use any filename
```

#### `put(string $key, mixed $value): void`

```php
server()->storage()->put('foo', 'bar');
```

#### `get(string $key, mixed $default = null): mixed`

```php
server()->storage()->get('foo', 'default value'); // foo
server()->storage()->get('baz', 'default value'); // default value
```

#### `remove(string ...$keys): self`

```php
server()->storage()->remove('foo'); // true
```

#### `has(string $key): bool`

```php
server()->storage()->has('foo'); // true
server()->storage()->has('baz'); // false
```

#### `filename(): string`

Returns path to file.

```php
server()->storage()->getPath();
```

## 🔹 Helpers (functions)

#### `server(): Server`

```php
server()->on(...);

// will be like:
use Porter\Server;
Server::getInstance()->on(...);
```

#### `worker(): Worker`

```php
worker()->connections;

// will be like:
use Porter\Server;
Server::getInstance()->getWorker()->connections;
```

#### `channel(string $id, string|array $key = null, mixed $default = null): mixed`

```php
$channel = channel('secret channel'); // get channel instance
$channel = server()->channel('secret channel');
$channel = server()->channels()->get('secret channel');
```

💡 See all helpers [here](/helpers.php).

## 🔹 Mappable methods (Macros)

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

# 🔹 Front-end

There is also a [small class](#javascript) for working with websockets on the client side.

```javascript
if (location.hostname == '127.0.0.1' || location.hostname == 'localhost') {
    const ws = new WebSocket(`ws://${location.hostname}:3737`); // on local dev
} else {
    const ws = new WebSocket(`wss://${location.hostname}:3737`); // on vps with ssl certificate
}

// options (optional, below default values)
let options = {
    pingInterval: 30000, // 30 sec.
    maxBodySize: 1e+6, // 1 mb.
}

const client = new Porter(ws, options);

// on client connected to server
client.connected = () => {
    // code...
}

// on client disconected from server
client.disconnected = () => {
    // code...
}

// on error
client.error = () => {
    // code...
}

// on raw `pong` event (if you needed it)
client.pong = () => {
    // code...
}

// close connection
client.close();

// event handler
client.on('ping', payload => {
    // available properties
    payload.type;
    payload.data;

    console.log(payload.data.foo) // bar
});

// send event to server
client.send('pong', {
    foo: 'bar',
});

// chain methods
client.send('ping').on('pong', payload => console.log(payload.type));

// send event and handle answer in one method
client.send('get online users', {/* ... */}, payload => {
    console.log(payload.type); // contains same event type 'get online users'
    console.log(payload.data.online); // and server answer e.g. '1337 users'
});

// pass channel_id and target_id for magic properties on back-end server
client.send('magical properties example', {
    channel_id: 'secret channel',
    target_id: 1337,

    // on backend php websocket server we can use $this->channel and $this->target magical properties.
});

// send raw websocket data
client.raw.send('hello world');

// send raw websocket data as json
client.raw.send(JSON.stringify({
    foo: 'bar',
}));

// handle raw websocket data from server
client.raw.on('hello from server', data => {
    console.log(data); // hello from server
});

// dont forget start listen websocket server!
client.listen();
```

# Used by

* [naplenke.online](https://naplenke.online?ref=porter) — The largest online cinema in Russia. Watching movies together.

# Credits

* [Chipslays](https://github.com/chipslays)
* [Workerman](https://github.com/walkor/workerman) by [walkor](https://github.com/walkor)
* [All contributors](https://github.com/chipslays/porter/graphs/contributors)

# License
MIT
