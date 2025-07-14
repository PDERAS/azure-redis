<?php

namespace Pderas\AzureRedisAuth\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Pderas\AzureRedisAuth\TokenManager;

class AzureRedisAuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/azure-redis-auth.php',
            'azure-redis-auth'
        );

        $this->registerAzureRedisDatabaseConnection();

        // Register the TokenManager as a singleton
        $this->app->singleton(TokenManager::class, function () {
            return new TokenManager();
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

        // Exit if the package is disabled
        if (!config('azure-redis-auth.enabled')) {
            return;
        }

        $manager = $this->app->make(TokenManager::class);

        $manager->setRedisCredentials();

        // Refresh once when the worker starts
        Event::listen(CommandStarting::class, function ($event) use ($manager) {
            $manager->setRedisCredentials();
        });

        // Refresh when workers start
        Event::listen(JobProcessing::class, function () use ($manager) {
            $manager->setRedisCredentials();
        });

        // Refresh credentials before processing any queued jobs
        Queue::before(function () use ($manager) {
            $manager->setRedisCredentials();
        });
    }

    /**
     * Register the Azure Redis database connection.
     */
    protected function registerAzureRedisDatabaseConnection(): void
    {
        $config = config('azure-redis-auth.azure_managed');

        $this->app['config']->set('database.redis.azure_managed', [
            'scheme'   => $config['scheme'],
            'host'     => $config['host'],
            'username' => '', // Will be set dynamically
            'password' => '', // Will be set dynamically
            'port'     => $config['port'],
            'database' => $config['database'],
        ]);
    }
}