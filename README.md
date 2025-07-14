# An Azure Redis package for Laravel

A Laravel package to authenticate with Azure Redis using **Managed Identity** tokens instead of static access keys.

## Requirements
- The server must be using an Azure VM with Managed Identities enabled
- The VM user must have the correct permissions to access the Redis service.
- For local token caching using the database, the cache table migration must be run. It can be generated and applied with the following commands:
```
php artisan cache:table
php artisan migration
```

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
This package will use the the following variables to make a Redis connection.
```php
'azure_managed' => [
    'scheme'   => env('REDIS_SCHEME', 'tls'),
    'url'      => env('REDIS_URL'),
    'host'     => env('REDIS_HOST', '127.0.0.1'),
    'username' => '',   // Will be set dynamically
    'password' => '',   // Will be set dynamically
    'port'     => env('REDIS_PORT', 6379),
    'database' => env('REDIS_DB', 0),
],
```

## Token Cache
The token generated with this package will be cached with the database driver by default. You can change this behaviour with the following env variable:
```ini
AZURE_REDIS_TOKEN_CACHE=
```
**Note:** "redis" cannot be used for this option as this will create a circular dependency

## Disable Manage Identity Auth
To fall back to standard Redis credentials (e.g. access key), set:
```ini
AZURE_REDIS_AUTH_ENABLED=false
```

## Optional Configuration Publishing
If you want to manually override these values with anything other than env variables, the config file can be published.
```
php artisan vendor:publish --tag=azure-redis-auth-config
```