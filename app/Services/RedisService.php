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
            $value = $this->redis->get($key);
            return $value ? json_decode($value, true) : null;
        } catch (\Exception $e) {
            Log::error("Error fetching from Redis: {$e->getMessage()}");
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
}
