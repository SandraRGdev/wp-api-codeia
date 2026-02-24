<?php
/**
 * Plugin Name: WP API CodeIA
 * Plugin URI: https://github.com/tu-usuario/wp-api-codeia
 * Description: Plugin para integración con la API de CodeIA
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://tu-sitio.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-api-codeia
 * Domain Path: /languages
 */

// Si se accede directamente, salir
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WP_API_CODEIA_VERSION', '1.0.0');
define('WP_API_CODEIA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_API_CODEIA_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Función de activación del plugin
 */
function wp_api_codeia_activate() {
    // Código de activación
}
register_activation_hook(__FILE__, 'wp_api_codeia_activate');

/**
 * Función de desactivación del plugin
 */
function wp_api_codeia_deactivate() {
    // Código de desactivación
}
register_deactivation_hook(__FILE__, 'wp_api_codeia_deactivate');

/**
 * Inicializar el plugin
 */
function wp_api_codeia_init() {
    // Inicialización del plugin
}
add_action('plugins_loaded', 'wp_api_codeia_init');
