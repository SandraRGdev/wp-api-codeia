<?php
/**
 * Bootstrap file for WP API Codeia plugin.
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia;

use WP_API_Codeia\Core\Bootstrapper;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Load configuration files.
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/default-config.php';

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
function wp_api_codeia_init() {
    /**
     * Bootstrapper class that initializes all plugin components.
     *
     * @var Bootstrapper
     */
    static $bootstrapper = null;

    if ($bootstrapper === null) {
        $bootstrapper = new Bootstrapper();
        $bootstrapper->boot();
    }

    return $bootstrapper;
}

// Initialize plugin on WordPress 'plugins_loaded' hook.
add_action('plugins_loaded', __NAMESPACE__ . '\\wp_api_codeia_init', 10);

/**
 * Get the plugin instance.
 *
 * @since 1.0.0
 *
 * @return Bootstrapper|null The plugin instance or null if not initialized.
 */
function wp_api_codeia() {
    static $instance = null;

    if ($instance === null) {
        $instance = apply_filters('wp_api_codeia_instance', null);
    }

    return $instance;
}
