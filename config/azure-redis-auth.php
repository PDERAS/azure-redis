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
    | Azure Redis Connection Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can specify the connection settings for Azure Redis.
    | This should match the connection name defined in your Redis 
    | configuration file (config/database.php). Make sure `username`
    | and `password` keys are set in the configuration to use Azure
    | Redis authentication.
    */

    'connection' => env('AZURE_REDIS_CONNECTION', 'default'),

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
];