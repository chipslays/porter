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

4. Add `IS_SERVER` var to `.env` file.

```bash
echo 'IS_SERVER=false' >> .env
```

>**NOTE:** On VPS with SSL certificate set `IS_SERVER=true`.

5. Run websocket server.

```bash
php websocket/server.php start
```
