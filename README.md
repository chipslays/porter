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
