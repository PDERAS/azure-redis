<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Azure Redis Authentication
    |--------------------------------------------------------------------------
    |
    | This option allows you to enable or disable the Azure Redis authentication.
    | When enabled, the package will automatically fetch the Redis credentials
    | from Azure's Metadata Service and set them in the Redis configuration.
    | If disabled, the package will not modify the Redis configuration.
    |
    */
    'enabled' => env('AZURE_REDIS_AUTH_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Token Cache Driver
    |--------------------------------------------------------------------------
    |
    | This option allows you to specify the cache driver used for storing
    | the Azure Redis authentication token. The default is 'database', but
    | you can change it to any cache driver EXCEPT 'redis'.
    */
    'token_cache' => env('AZURE_REDIS_TOKEN_CACHE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Azure Managed Redis Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains the configuration for the Azure Managed Redis instance.
    | You can specify the scheme, URL, host, username, password, port, and database.
    | Make sure to set the environment variables accordingly.
    |
    | Note: The username and password will be set dynamically by the package.
    |       Do not set them here.
    */
    'azure_managed' => [
        'scheme'   => env('REDIS_SCHEME', 'tls'),
        'url'      => env('REDIS_URL'),
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'username' => '',
        'password' => '',
        'port'     => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
    ],
];