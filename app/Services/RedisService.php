<?php

namespace App\Services;

use Predis\Client;
use Illuminate\Support\Facades\Log;

class RedisService
{
    protected $redis;

    public function __construct()
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host'   => env('REDIS_HOST', '127.0.0.1'), // Use environment variables for configuration
            'port'   => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 0),
        ]);
    }

    /**
     * Get a value from Redis by key.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        try {
            // Ensure Redis connection is alive
            $this->redis->ping();

            $value = $this->redis->get($key);

            // Key does not exist
            if ($value === null) {
                Log::warning('Redis key not found', ['key' => $key]);
                return null;
            }

            // Key exists but contains empty/blank value
            if (trim($value) === '') {
                Log::warning('Redis key exists but value is empty', ['key' => $key]);
                return null;
            }

            // Decode JSON
            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode JSON from Redis', [
                    'key' => $key,
                    'error' => json_last_error_msg(),
                    'value_preview' => substr($value, 0, 150),
                ]);
                return null;
            }

            // Success
            Log::info('Redis value retrieved successfully', [
                'key' => $key,
                'type' => gettype($decoded),
                'items' => is_array($decoded) ? count($decoded) : null,
            ]);

            return $decoded;

        } catch (\Throwable $e) {

            // Log locally
            Log::error('Redis fetch error', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            // Send alert to Slack
            Log::channel('slack')->error('Redis connection error', [
                'key' => $key,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }


    /**
     * Set a value in Redis by key.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $expireInSeconds
     * @return bool
     */
    public function set(string $key, $value, int $expireInSeconds = null)
    {
        try { 
            $value = json_encode($value);  // Ensure it's stored as JSON
            $result = $this->redis->set($key, $value);
            if ($expireInSeconds) {
                $this->redis->expire($key, $expireInSeconds);
            }
            return $result;
        } catch (\Exception $e) {
            Log::error("Error saving to Redis: {$e->getMessage()}");
            return false;
        }
    }

    public function delete(string $key)
    {
        return $this->redis->del($key);
    }

    /**
     * Add a member to a Redis set (for barcode tracking)
     *
     * @param string $setKey
     * @param string $member
     * @return int
     */
    public function addToSet(string $setKey, string $member)
    {
        try {
            return $this->redis->sadd($setKey, $member);
        } catch (\Exception $e) {
            Log::error("Error adding to Redis set: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Check if a member exists in a Redis set
     *
     * @param string $setKey
     * @param string $member
     * @return bool
     */
    public function isInSet(string $setKey, string $member): bool
    {
        try {
            return (bool) $this->redis->sismember($setKey, $member);
        } catch (\Exception $e) {
            Log::error("Error checking Redis set membership: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get the count of members in a Redis set
     *
     * @param string $setKey
     * @return int
     */
    public function getSetCount(string $setKey): int
    {
        try {
            return $this->redis->scard($setKey);
        } catch (\Exception $e) {
            Log::error("Error getting Redis set count: {$e->getMessage()}");
            return 0;
        }
    }

    /**
     * Remove a member from a Redis set
     *
     * @param string $setKey
     * @param string $member
     * @return int
     */
    public function removeFromSet(string $setKey, string $member)
    {
        try {
            return $this->redis->srem($setKey, $member);
        } catch (\Exception $e) {
            Log::error("Error removing from Redis set: {$e->getMessage()}");
            return false;
        }
    }
}
