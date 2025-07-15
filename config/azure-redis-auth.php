<?php

return [
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