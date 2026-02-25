<?php
/**
 * Cache Manager
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Utils\Cache;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

use WP_API_Codeia\Core\Interfaces\ServiceInterface;
use WP_API_Codeia\Core\Container;

/**
 * Cache manager for plugin caching.
 *
 * @since 1.0.0
 */
class CacheManager implements ServiceInterface
{
    /**
     * Cache group name.
     *
     * @since 1.0.0
     *
     * @var string
     */
    const CACHE_GROUP = 'wp_api_codeia';

    /**
     * Whether caching is enabled.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Cache driver.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $driver;

    /**
     * Default TTL values.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $ttl;

    /**
     * Create a new CacheManager instance.
     *
     * @since 1.0.0
     *
     * @param ?Container $container Optional DI container.
     */
    public function __construct(?Container $container = null)
    {
        // Container is not used but accepted for service provider compatibility
        $config = wp_api_codeia_config('cache', array());

        $this->enabled = isset($config['enabled']) ? $config['enabled'] : true;
        $this->driver = isset($config['driver']) ? $config['driver'] : 'auto';
        $this->ttl = isset($config['ttl']) ? $config['ttl'] : array(
            'data' => 300,
            'schema' => 3600,
            'permissions' => 900,
        );
    }

    /**
     * Register the cache service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        // Initialize cache driver
        if ($this->enabled) {
            $this->initDriver();
        }
    }

    /**
     * Boot the cache service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        // Set up scheduled cache cleanup
        if ($this->enabled && !wp_next_scheduled('wp_api_codeia_cleanup_cache')) {
            wp_schedule_event(time(), 'hourly', 'wp_api_codeia_cleanup_cache');
        }
    }

    /**
     * Initialize cache driver.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function initDriver()
    {
        // Auto-detect best available cache driver
        if ($this->driver === 'auto') {
            if (wp_using_ext_object_cache()) {
                // Using Redis, Memcached, or other external object cache
                $this->driver = 'object-cache';
            } else {
                // Fall back to transients
                $this->driver = 'transient';
            }
        }
    }

    /**
     * Get a value from cache.
     *
     * @since 1.0.0
     *
     * @param string $key Cache key.
     * @param mixed  $default Default value if not found.
     * @return mixed Cached value or default.
     */
    public function get($key, $default = null)
    {
        if (!$this->enabled) {
            return $default;
        }

        $fullKey = $this->buildKey($key);

        switch ($this->driver) {
            case 'object-cache':
                $value = wp_cache_get($fullKey, self::CACHE_GROUP, $default);
                break;
            case 'transient':
            default:
                $value = get_transient($fullKey);
                if ($value === false) {
                    $value = $default;
                }
                break;
        }

        return $value;
    }

    /**
     * Set a value in cache.
     *
     * @since 1.0.0
     *
     * @param string $key Cache key.
     * @param mixed  $value Value to cache.
     * @param int    $ttl Time to live in seconds.
     * @return bool True on success, false on failure.
     */
    public function set($key, $value, $ttl = null)
    {
        if (!$this->enabled) {
            return false;
        }

        $fullKey = $this->buildKey($key);

        if ($ttl === null) {
            $ttl = $this->getDefaultTtl($key);
        }

        switch ($this->driver) {
            case 'object-cache':
                return wp_cache_set($fullKey, $value, $ttl, self::CACHE_GROUP);
            case 'transient':
            default:
                return set_transient($fullKey, $value, $ttl);
        }
    }

    /**
     * Delete a value from cache.
     *
     * @since 1.0.0
     *
     * @param string $key Cache key.
     * @return bool True on success, false on failure.
     */
    public function delete($key)
    {
        if (!$this->enabled) {
            return false;
        }

        $fullKey = $this->buildKey($key);

        switch ($this->driver) {
            case 'object-cache':
                return wp_cache_delete($fullKey, self::CACHE_GROUP);
            case 'transient':
            default:
                return delete_transient($fullKey);
        }
    }

    /**
     * Clear all cache or by pattern.
     *
     * @since 1.0.0
     *
     * @param string $pattern Optional pattern to match keys.
     * @return int Number of keys cleared.
     */
    public function clear($pattern = '')
    {
        if (!$this->enabled) {
            return 0;
        }

        if ($this->driver === 'object-cache') {
            // Clear all keys in our group
            if (empty($pattern)) {
                wp_cache_flush_group(self::CACHE_GROUP);
                return 1; // Cannot count exact number in object cache
            }
        }

        // For transients, we need to track our keys
        $keys = $this->getKeys($pattern);
        $count = 0;

        foreach ($keys as $key) {
            if ($this->delete($key)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Remember a value indefinitely (until manually cleared).
     *
     * @since 1.0.0
     *
     * @param string $key Cache key.
     * @param mixed  $callback Callback to generate value.
     * @return mixed Cached or generated value.
     */
    public function remember($key, $callback)
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = is_callable($callback) ? call_user_func($callback) : $callback;

        $this->set($key, $value);

        return $value;
    }

    /**
     * Get or set a value using a callback.
     *
     * @since 1.0.0
     *
     * @param string   $key Cache key.
     * @param callable $callback Callback to generate value.
     * @param int      $ttl Time to live.
     * @return mixed Cached or generated value.
     */
    public function rememberCached($key, callable $callback, $ttl = null)
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = call_user_func($callback);

        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Build a full cache key.
     *
     * @since 1.0.0
     *
     * @param string $key Base cache key.
     * @return string Full cache key.
     */
    protected function buildKey($key)
    {
        return self::CACHE_GROUP . ':' . $key;
    }

    /**
     * Get default TTL for a cache key type.
     *
     * @since 1.0.0
     *
     * @param string $key Cache key.
     * @return int TTL in seconds.
     */
    protected function getDefaultTtl($key)
    {
        // Determine TTL based on key prefix
        if (strpos($key, 'schema') === 0) {
            return isset($this->ttl['schema']) ? $this->ttl['schema'] : 3600;
        }

        if (strpos($key, 'permissions') === 0) {
            return isset($this->ttl['permissions']) ? $this->ttl['permissions'] : 900;
        }

        return isset($this->ttl['data']) ? $this->ttl['data'] : 300;
    }

    /**
     * Get all cache keys.
     *
     * @since 1.0.0
     *
     * @param string $pattern Optional pattern to match keys.
     * @return array Array of cache keys.
     */
    protected function getKeys($pattern = '')
    {
        // For transients, we maintain a separate list
        $keys = get_option('wp_api_codeia_cache_keys', array());

        if (!empty($pattern)) {
            $regex = '/' . preg_quote($pattern, '/') . '/';
            $keys = preg_grep($regex, $keys);
        }

        return $keys;
    }

    /**
     * Register a cache key.
     *
     * @since 1.0.0
     *
     * @param string $key Cache key.
     * @return void
     */
    public function registerKey($key)
    {
        $keys = get_option('wp_api_codeia_cache_keys', array());

        if (!in_array($key, $keys, true)) {
            $keys[] = $key;
            update_option('wp_api_codeia_cache_keys', $keys);
        }
    }

    /**
     * Unregister a cache key.
     *
     * @since 1.0.0
     *
     * @param string $key Cache key.
     * @return void
     */
    public function unregisterKey($key)
    {
        $keys = get_option('wp_api_codeia_cache_keys', array());
        $keys = array_filter($keys, function ($k) use ($key) {
            return $k !== $key;
        });

        update_option('wp_api_codeia_cache_keys', array_values($keys));
    }

    /**
     * Check if caching is enabled.
     *
     * @since 1.0.0
     *
     * @return bool True if enabled, false otherwise.
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Enable or disable caching.
     *
     * @since 1.0.0
     *
     * @param bool $enabled Whether caching should be enabled.
     * @return void
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Get the current cache driver.
     *
     * @since 1.0.0
     *
     * @return string Cache driver name.
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Generate a cache key hash.
     *
     * @since 1.0.0
     *
     * @param array $data Data to hash.
     * @return string Hash string.
     */
    public function generateHash(array $data)
    {
        return md5(serialize($data));
    }
}
