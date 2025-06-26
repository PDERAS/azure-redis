# An Azure Redis package for Laravel

A Laravel package to authenticate with Azure Redis using **Managed Identity** tokens instead of static access keys.

## Requirements
- The server must be using an Azure VM with Managed Identities enabled
- The VM user must have the correct permissions to access the Redis service.

## Installation
Add repository to `composer.json`
```json
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:PDERAS/azure-redis.git"
    },
]
```

### Install via composer 
```sh
composer require pderas/azure-redis
```

### Publish config file (optional)
```sh
php artisan vendor:publish --provider="Pderas\AzureRedis\AzureRedisAuthServiceProvider"
```

# Usage
This package will use the `default` redis connection defined in `config/databases.php`. To use a different connection, change the setting `AZURE_REDIS_CONNECTION` in your `.env` to a different connection.
```ini
AZURE_REDIS_CONNECTION=azure
```

**Whichever connection is used, you MUST define the `username` and `password` keys**

```php
'redis' => [
    'default' => [
        'scheme'   => env('REDIS_SCHEME', 'tls'),
        'url'      => env('REDIS_URL'),
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME', ''),        // Need these
        'password' => env('REDIS_PASSWORD', ''),        // Need these
        'port'     => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
],
```

## Disable Manage Identity Auth

To fall back to standard Redis credentials (e.g. access key), set:
```ini
AZURE_REDIS_AUTH_ENABLED=false
```