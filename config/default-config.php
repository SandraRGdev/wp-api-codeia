<?php
/**
 * Default configuration for WP API Codeia plugin.
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get default plugin configuration.
 *
 * @since 1.0.0
 *
 * @return array Default configuration array.
 */
function wp_api_codeia_get_default_config() {
    return array(
        // API Configuration
        'api_namespace' => WP_API_CODEIA_API_NAMESPACE,
        'api_version' => WP_API_CODEIA_API_VERSION,
        'default_per_page' => 10,
        'max_per_page' => 100,

        // Authentication
        'auth_methods' => array(
            'jwt' => array(
                'enabled' => true,
                'algorithm' => WP_API_CODEIA_JWT_ALGORITHM,
                'access_ttl' => WP_API_CODEIA_JWT_ACCESS_TTL,
                'refresh_ttl' => WP_API_CODEIA_JWT_REFRESH_TTL,
                'lease_ttl' => WP_API_CODEIA_JWT_LEASE_TTL,
            ),
            'api_key' => array(
                'enabled' => true,
                'prefix' => WP_API_CODEIA_API_KEY_PREFIX,
                'rate_limit' => 1000,
                'rate_limit_window' => 3600,
            ),
            'app_password' => array(
                'enabled' => false,
                'rate_limit' => 500,
            ),
        ),

        // Post Types
        'enabled_post_types' => array('post', 'page'),
        'excluded_post_types' => array('attachment', 'revision', 'nav_menu_item'),

        // Permissions
        'default_deny' => false,
        'role_overrides' => array(
            'administrator' => array(
                'read' => true,
                'create' => true,
                'update' => true,
                'delete' => true,
                'publish' => true,
            ),
            'editor' => array(
                'read' => true,
                'create' => true,
                'update' => true,
                'delete' => false,
                'publish' => true,
                'own_only' => false,
            ),
            'author' => array(
                'read' => true,
                'create' => true,
                'update' => true,
                'delete' => false,
                'publish' => true,
                'own_only' => true,
            ),
            'contributor' => array(
                'read' => true,
                'create' => true,
                'update' => false,
                'delete' => false,
                'publish' => false,
                'own_only' => true,
            ),
            'subscriber' => array(
                'read' => true,
                'create' => false,
                'update' => false,
                'delete' => false,
                'publish' => false,
            ),
        ),

        // Upload
        'upload' => array(
            'max_file_size' => 10485760, // 10MB in bytes
            'allowed_mime_types' => array(
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/pdf',
            ),
            'strip_exif' => true,
            'regenerate_thumbnails' => true,
        ),

        // Cache
        'cache' => array(
            'enabled' => true,
            'ttl' => array(
                'data' => 300,       // 5 minutes
                'schema' => 3600,    // 1 hour
                'permissions' => 900, // 15 minutes
            ),
            'driver' => 'auto', // auto, redis, memcached, transient
        ),

        // Rate Limiting
        'rate_limiting' => array(
            'enabled' => true,
            'per_ip' => 1000,
            'per_ip_window' => 3600,
            'per_user' => 5000,
            'per_user_window' => 3600,
            'ban_duration' => 3600,
        ),

        // Logging
        'logging' => array(
            'enabled' => true,
            'level' => 'warning', // debug, info, warning, error
            'retention_days' => 30,
            'log_auth' => true,
            'log_requests' => false,
            'log_errors' => true,
        ),

        // Documentation
        'docs' => array(
            'auto_generate' => true,
            'openapi_version' => '3.0',
            'include_examples' => true,
            'swagger_ui_enabled' => true,
        ),

        // Integrations
        'integrations' => array(
            'acf' => array(
                'enabled' => class_exists('ACF'),
            ),
            'jetengine' => array(
                'enabled' => class_exists('Jet_Engine'),
            ),
            'metabox' => array(
                'enabled' => class_exists('RWMB_Loader'),
            ),
        ),

        // Debug
        'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
    );
}

/**
 * Get a specific config value.
 *
 * @since 1.0.0
 *
 * @param string $key Config key (dot notation supported).
 * @param mixed  $default Default value if key not found.
 * @return mixed Config value.
 */
function wp_api_codeia_config($key, $default = null) {
    static $config = null;

    if ($config === null) {
        // Get default config
        $config = wp_api_codeia_get_default_config();

        // Override with options from database
        $saved_config = get_option('wp_api_codeia_config', array());
        $config = array_replace_recursive($config, $saved_config);
    }

    // Support dot notation for nested keys
    $keys = explode('.', $key);
    $value = $config;

    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }

    return $value;
}
