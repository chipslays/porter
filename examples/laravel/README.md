## Laravel Websocket Server

Primitive integration Porter to Laravel project.

## Installation

1. Install Porter via Composer.

```php
composer require chipslays/porter ^1.x
```

2. Put javascript file in `app.blade.php`

```html
<script src="https://cdn.jsdelivr.net/gh/chipslays/porter@latest/dist/porter.min.js"></script>
```

1. Place template in root application folder.

```bash
php vendor/bin/porter template:laravel ./websocket
```

4. Add `IS_SERVER` var to `.env` file.

```bash
echo 'IS_SERVER=false' >> .env
```

>**NOTE:** On VPS with SSL certificate set `IS_SERVER=true`.

5. Run websocket server.

```bash
php websocket/server.php start
```

6. Create websocker client in views.

```html
<script>
    const ws = new WebSocket('ws://localhost:3737');
    const client = new Porter(ws);

    client.connected = () => {
        client.event('ping');
    }

    client.on('pong', payload => {
        console.log(payload);
    });

    client.listen();
</script>
```

7. Run PHP server

```bash
php artisan serve
```

8. Go to http://127.0.0.1:8000 and open dev tools.

If all ok, you can see console log: `{type: 'pong', timestamp: 1669092112, data: Array(0)}`