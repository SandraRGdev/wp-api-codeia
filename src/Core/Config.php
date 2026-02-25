<?php
/**
 * Configuration Manager
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Core;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Configuration manager for the plugin.
 *
 * @since 1.0.0
 */
class Config implements Interfaces\ServiceInterface
{
    /**
     * Configuration array.
     *
     * @var array
     */
    protected $config = array();

    /**
     * Create a new Config instance.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Register the configuration service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        // Registration is handled in constructor
    }

    /**
     * Boot the configuration service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        // Nothing to boot for now
    }

    /**
     * Load configuration from defaults and database.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function loadConfig()
    {
        // Get default config
        $defaults = $this->getDefaultConfig();

        // Get saved config from database
        $saved = get_option('wp_api_codeia_config', array());

        // Merge saved config with defaults
        $this->config = $this->arrayReplaceRecursive($defaults, $saved);
    }

    /**
     * Get default configuration.
     *
     * @since 1.0.0
     *
     * @return array Default configuration.
     */
    protected function getDefaultConfig()
    {
        return wp_api_codeia_get_default_config();
    }

    /**
     * Get a config value.
     *
     * @since 1.0.0
     *
     * @param string $key Config key (dot notation supported).
     * @param mixed  $default Default value if key not found.
     * @return mixed Config value.
     */
    public function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set a config value.
     *
     * @since 1.0.0
     *
     * @param string $key Config key.
     * @param mixed  $value Config value.
     * @return void
     */
    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $config[$k] = $value;
            } else {
                if (!isset($config[$k]) || !is_array($config[$k])) {
                    $config[$k] = array();
                }
                $config = &$config[$k];
            }
        }
    }

    /**
     * Save configuration to database.
     *
     * @since 1.0.0
     *
     * @return bool True on success, false on failure.
     */
    public function save()
    {
        return update_option('wp_api_codeia_config', $this->config);
    }

    /**
     * Get all configuration.
     *
     * @since 1.0.0
     *
     * @return array All configuration.
     */
    public function all()
    {
        return $this->config;
    }

    /**
     * Recursively replace arrays.
     *
     * @since 1.0.0
     *
     * @param array $array1 Base array.
     * @param array $array2 Array to merge.
     * @return array Merged array.
     */
    protected function arrayReplaceRecursive(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->arrayReplaceRecursive($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}
