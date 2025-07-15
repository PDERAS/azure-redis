<?php

namespace Pderas\AzureRedisAuth\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use Pderas\AzureRedisAuth\Connectors\AzureRedisConnector;
use Pderas\AzureRedisAuth\TokenManager;

class AzureRedisAuthServiceProvider extends ServiceProvider
{
    /**
     * The name of the Redis driver used by this package.
     */
    private const DRIVER_NAME = 'azure';

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/azure-redis-auth.php',
            'azure-redis-auth'
        );

        // Register the TokenManager as a singleton
        $this->app->singleton(TokenManager::class, function () {
            return new TokenManager();
        });

        $this->app->extend('redis', function ($manager) {
            $manager->extend(self::DRIVER_NAME, function () {
                return new AzureRedisConnector(
                    $this->app->make(TokenManager::class)
                );
            });

            return $manager;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish the config file
        $this->publishes([
            __DIR__ . '/../../config/azure-redis-auth.php' => config_path('azure-redis-auth.php'),
        ], 'azure-redis-auth-config');

        // Exit if not using the 'azure' redis client
        if (!config('database.redis.client') === self::DRIVER_NAME) {
            return;
        }

        // Refresh once when the worker starts
        Event::listen(CommandStarting::class, function () {
            $this->purgeConnectionsIfNeeded();
        });

        // Refresh credentials before processing any queued jobs
        Queue::before(function () {
            $this->purgeConnectionsIfNeeded();
        });
    }

    /**
     * Purge Redis connections if the token is not valid.
     * This ensures that all Redis connections are reset to use the latest credentials.
     */
    public function purgeConnectionsIfNeeded(): void
    {
        $manager = $this->app->make(TokenManager::class);

        if (!$manager->tokenIsValid()) {
            // Purge all Redis connections to ensure they use the latest credentials
            foreach (config('database.redis', []) as $connection_name => $config) {
                // Skip client and option keys
                if (in_array($connection_name, ['client', 'options'])) {
                    continue;
                }
                // Purge the connection to reset credentials
                Redis::purge($connection_name);
            }
        }
    }
}