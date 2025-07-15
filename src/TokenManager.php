<?php

namespace Pderas\AzureRedisAuth;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use \Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class TokenManager
{
    /**
     * The cache key for the Azure Redis authentication token.
     */
    private string $cache_key = 'azure_redis_token';

    /**
     * The cache key for the decoded token data.
     */
    private string $cache_data_key = 'azure_redis_token_data';

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
     * Refresh Redis credentials if they are expired or not set.
     *
     * This method checks if the token is still valid and refreshes it if necessary.
     * It is called when a job starts processing or when a queue worker starts.
     */
    public function refreshCredentialsIfNeeded(): void
    {
        if ($this->tokenIsValid()) {
            return;
        }

        $this->refreshToken();
    }

    /**
     * Checks if the current token is valid.
     *
     * A token is considered valid if it exists and has not expired.
     */
    public function tokenIsValid(): bool
    {
        // Try to get token and data from cache
        $cache = $this->getCache();
        $encrypted_token = $cache->get($this->cache_key);
        $encrypted_token_data = $cache->get($this->cache_data_key);

        // Invalid if token or data is not found in cache
        if (!$encrypted_token || !$encrypted_token_data) {
            return false;
        }

        try {
            $this->token = Crypt::decrypt($encrypted_token);
            $this->token_data = Crypt::decrypt($encrypted_token_data);
        } catch (\Exception $e) {
            // If decryption fails, treat the token as invalid
            return false;
        }

        if (empty($this->token_data)) {
            return false;
        }

        $expiry = $this->getExpiry();

        // If expiry is null, assume the token is invalid
        if (!$expiry) {
            return false;
        }

        // Check if the token is still valid (not expired)
        return $expiry->isFuture();
    }

    /**
     * Retrieves the credentials for Redis authentication.
     */
    private function refreshToken(): void
    {
        $response = Http::withHeaders([
            'Metadata' => 'true',
        ])->get('http://169.254.169.254/metadata/identity/oauth2/token', [
            'api-version' => self::$api_version,
            'resource'    => 'https://redis.azure.com/',
        ]);

        $this->token = $response->json('access_token', '');

        $this->decodeToken();

        // Store for 20 hours
        $this->getCache()->put($this->cache_key, Crypt::encrypt($this->token), $this->getCacheTtl());
    }

    /**
     * Retrieves the username from the token.
     */
    public function getUsername(): ?string
    {
        $this->refreshCredentialsIfNeeded();

        return $this->token_data['oid'] ?? null;
    }

    /**
     * Retrieves the username from the token.
     */
    public function getPassword(): ?string
    {
        $this->refreshCredentialsIfNeeded();

        return $this->token;
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

        $this->getCache()->put($this->cache_data_key, Crypt::encrypt($this->token_data), $this->getCacheTtl());
    }

    /**
     * Gets the cache TTL (time to live) for the token.
     */
    private function getCacheTtl(): int
    {
        $expiry = $this->getExpiry();

        // If expiry not set, default to 20 hours
        if (!$expiry) {
            return 20 * 60 * 60;
        }

        // Calculate the TTL (time to live) in seconds with a buffer of 1 hour
        // to ensure the token is refreshed before it expires
        $ttl = $expiry->diffInSeconds(Carbon::now()) - 3600;

        return max($ttl, 30); // Ensure TTL is not negative
    }

    /**
     * Gets the cache repository for the configured cache driver.
     */
    private function getCache(): \Illuminate\Contracts\Cache\Repository
    {
        // Get the cache driver configured in Laravel
        $driver = $this->getCacheDriver();

        // Return the cache repository for the specified driver
        return Cache::store($driver);
    }

    /**
     * Gets the cache driver configured in Laravel.
     */
    private function getCacheDriver(): string
    {
        // Return the cache driver configured in Laravel
        $driver = config('azure-redis-auth.token_cache', 'database');

        // Redis is not allowed as a cache driver for storing tokens
        if ($driver === 'redis') {
            throw new \Exception('The "redis" cache driver cannot be used for storing Azure Redis authentication tokens.');
        }

        return $driver;
    }
}
