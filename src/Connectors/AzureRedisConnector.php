<?php

namespace Pderas\AzureRedisAuth\Connectors;

use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Redis\Connectors\PhpRedisConnector;
use Pderas\AzureRedisAuth\TokenManager;

class AzureRedisConnector extends PhpRedisConnector
{
    public function __construct(protected TokenManager $token_manager)
    {
    }

    /**
     * Create a new connection.
     */
    public function connect(array $config, array $options = []): PhpRedisConnection
    {
        // Get credentials from TokenManager
        $username = $this->token_manager->getUsername();
        $password = $this->token_manager->getPassword();

        // Inject credentials into config
        $config['username'] = $username;
        $config['password'] = $password;

        // Continue with the parent connect method
        return parent::connect($config, $options);
    }
}