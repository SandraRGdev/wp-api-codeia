<?php
/**
 * Plugin Name: WP API Codeia
 * Plugin URI: https://github.com/wp-api-codeia/wp-api-codeia
 * Description: Transforma WordPress en una API REST configurable y personalizable con autenticación JWT, endpoints dinámicos, permisos granulares y documentación OpenAPI automática.
 * Version: 1.0.0
 * Author: WP API Codeia Team
 * Author URI: https://codeia.io
 * Text Domain: wp-api-codeia
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Network: true
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Plugin version constant.
define('WP_API_CODEIA_VERSION', '1.0.0');

// Plugin root path constant.
define('WP_API_CODEIA_PLUGIN_FILE', __FILE__);
define('WP_API_CODEIA_PLUGIN_DIR', __DIR__);
define('WP_API_CODEIA_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if Composer autoloader exists, otherwise use fallback.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Use fallback PSR-4 autoloader
    require_once __DIR__ . '/src/autoloader.php';
}

// Load bootstrap file.
require_once __DIR__ . '/bootstrap.php';

// Activation and deactivation hooks.
register_activation_hook(__FILE__, 'wp_api_codeia_activate');
register_deactivation_hook(__FILE__, 'wp_api_codeia_deactivate');

/**
 * Plugin activation handler.
 *
 * @since 1.0.0
 *
 * @param bool $network_wide Whether the plugin is being activated network-wide.
 */
function wp_api_codeia_activate($network_wide = false) {
    // Check PHP version.
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        wp_die(sprintf(
            /* translators: %s: Required PHP version */
            __('WP API Codeia requiere PHP 7.4 o superior. Tu versión actual es %s.', 'wp-api-codeia'),
            PHP_VERSION
        ));
    }

    // Create database tables.
    wp_api_codeia_create_tables();

    // Flush rewrite rules.
    flush_rewrite_rules();

    // Set default options.
    wp_api_codeia_set_default_options();
}

/**
 * Plugin deactivation handler.
 *
 * @since 1.0.0
 */
function wp_api_codeia_deactivate() {
    // Flush rewrite rules.
    flush_rewrite_rules();
}

/**
 * Create database tables for tokens and API keys.
 *
 * @since 1.0.0
 */
function wp_api_codeia_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Tokens table.
    $table_name = $wpdb->prefix . 'codeia_tokens';
    $sql = "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        token_id varchar(191) NOT NULL,
        user_id bigint(20) UNSIGNED NOT NULL,
        token_type varchar(20) NOT NULL DEFAULT 'access',
        expires_at datetime DEFAULT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY token_id (token_id),
        KEY user_id (user_id),
        KEY token_type (token_type),
        KEY expires_at (expires_at)
    ) $charset_collate;";

    // API Keys table.
    $table_name = $wpdb->prefix . 'codeia_api_keys';
    $sql2 = "CREATE TABLE $table_name (
        api_key_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        api_key varchar(191) NOT NULL,
        user_id bigint(20) UNSIGNED NOT NULL,
        name varchar(255) NOT NULL,
        scopes text NOT NULL,
        last_used datetime DEFAULT NULL,
        last_ip varchar(45) DEFAULT NULL,
        created_at datetime NOT NULL,
        expires_at datetime DEFAULT NULL,
        rate_limit int DEFAULT 1000,
        rate_limit_window int DEFAULT 3600,
        is_revoked tinyint(1) DEFAULT 0,
        PRIMARY KEY  (api_key_id),
        UNIQUE KEY api_key (api_key),
        KEY user_id (user_id),
        KEY is_revoked (is_revoked),
        KEY expires_at (expires_at)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    dbDelta($sql2);
}

/**
 * Set default plugin options.
 *
 * @since 1.0.0
 */
function wp_api_codeia_set_default_options() {
    $defaults = array(
        'api_namespace' => 'wp-custom-api',
        'api_version' => 'v1',
        'default_per_page' => 10,
        'max_per_page' => 100,
        'jwt_algorithm' => 'RS256',
        'jwt_access_ttl' => 3600,
        'jwt_refresh_ttl' => 2592000,
        'jwt_lease_ttl' => 300,
        'enabled_post_types' => array('post', 'page'),
        'debug_mode' => false,
    );

    foreach ($defaults as $key => $value) {
        if (get_option('wp_api_codeia_' . $key) === false) {
            update_option('wp_api_codeia_' . $key, $value);
        }
    }
}
