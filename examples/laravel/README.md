## Laravel

Primitive integration Porter to Laravel project.

## Installation

### By command
1. `php vendor/bin/porter template:laravel ./websocket`.

### Manual
1. Put this files to folder (e.g. `/websocket`) in root of Laravel project.

In both cases don't forget update your .env file:

```bash
echo 'IS_SERVER=false' >> .env
```

>**NOTE:** On server `IS_SERVER=true`.

## Run
```bash
php websocket/server.php start
```
