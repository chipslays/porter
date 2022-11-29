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

3. Place template in root application folder.

```bash
php vendor/bin/porter template:laravel ./websocket
```

Tip: You can also create a event class.

```bash
php vendor/bin/porter make:event ./websocket/Events/Example.php "example event"
```

4. Add variables to `.env` file.

```bash
echo 'PORTER_SSL=false' >> .env
echo 'PORTER_LOCAL_CERT=/etc/letsencrypt/live/example.com/cert.pem' >> .env
echo 'PORTER_LOCAL_PK=/etc/letsencrypt/live/example.com/privkey.pem' >> .env
```

>**NOTE:** On VPS with SSL certificate set `PORTER_SSL=true` and provide path to certs in `PORTER_LOCAL_CERT` and `PORTER_LOCAL_PK`.

5. Run websocket server.

```bash
php websocket/server.php start
```

```bash
php websocket/server.php start -d # run in background as daemon
```

6. Create websocket client in views.

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
