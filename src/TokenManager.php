<?php

namespace Pderas\AzureRedisAuth;

use \Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class TokenManager
{
    /**
     * The Azure Redis authentication token.
     */
    protected ?string $token = null;

    /**
     * The decoded token data.
     */
    protected array $token_data = [];

    /**
     * The API version for Azure Metadata Service.
     */
    private static string $api_version = '2023-11-15';

    /**
     * Set Redis credentials from Azure Metadata Service.
     */
    public function setRedisCredentials(): void
    {
        $this->fetchToken();

        if ($this->token) {
            $connection_name = config('azure-redis-auth.connection', 'default');

            Redis::purge($connection_name);

            config(["database.redis.{$connection_name}.username" => $this->getUsername()]);
            config(["database.redis.{$connection_name}.password" => $this->token]);
        } else {
            throw new \Exception('Failed to retrieve Redis credentials from Azure Metadata Service.');
        }
    }

    /**
     * Refresh Redis credentials if they are expired or not set.
     *
     * This method checks if the token is still valid and refreshes it if necessary.
     * It is called when a job starts processing or when a queue worker starts.
     */
    public function refreshCredentialsIfNeeded(): void
    {
        $expiry = $this->getExpiry();

        // Check if the token is still valid
        if ($expiry && $expiry->isFuture()) {
            return;
        }

        $this->setRedisCredentials();
    }

    /**
     * Retrieves the credentials for Redis authentication.
     */
    private function fetchToken(): void
    {
        $response = Http::withHeaders([
            'Metadata' => 'true',
        ])->get('http://169.254.169.254/metadata/identity/oauth2/token', [
            'api-version' => self::$api_version,
            'resource'    => 'https://redis.azure.com/',
        ]);

        $this->token = $response->json('access_token', '');
        $this->decodeToken();
    }

    /**
     * Retrieves the username from the token.
     */
    private function getUsername(): ?string
    {
        return $this->token_data['oid'] ?? null;
    }

    /**
     * Retrieves the expiry from the token.
     */
    private function getExpiry(): ?Carbon
    {
        $expiry = $this->token_data['exp'] ?? null;

        return $expiry ? Carbon::createFromTimestamp($expiry) : null;
    }

    /**
     * Decodes the JWT token and sets the token data.
     */
    private function decodeToken(): void
    {
        // No token to decode
        if (empty($this->token)) {
            return;
        }

        $parts = explode('.', $this->token);

        // Invalid token format
        if (count($parts) !== 3) {
            return;
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'));

        $this->token_data = json_decode($payload, true);
    }
}
