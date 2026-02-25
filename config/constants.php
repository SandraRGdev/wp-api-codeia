<?php
/**
 * Global constants for WP API Codeia plugin.
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Plugin paths (use plugin root from main file).
define('WP_API_CODEIA_PATH', WP_API_CODEIA_PLUGIN_DIR . '/');
define('WP_API_CODEIA_URL', plugin_dir_url(WP_API_CODEIA_PLUGIN_FILE));
define('WP_API_CODEIA_INC_PATH', WP_API_CODEIA_PATH . 'src/');
define('WP_API_CODEIA_CONFIG_PATH', WP_API_CODEIA_PATH . 'config/');
define('WP_API_CODEIA_TEMPLATES_PATH', WP_API_CODEIA_PATH . 'templates/');
define('WP_API_CODEIA_TESTS_PATH', WP_API_CODEIA_PATH . 'tests/');

// API namespaces.
define('WP_API_CODEIA_API_NAMESPACE', 'wp-custom-api');
define('WP_API_CODEIA_API_VERSION', 'v1');
define('WP_API_CODEIA_REST_URL', '/' . WP_API_CODEIA_API_NAMESPACE . '/' . WP_API_CODEIA_API_VERSION . '/');

// Authentication constants.
define('WP_API_CODEIA_AUTH_JWT', 'jwt');
define('WP_API_CODEIA_AUTH_API_KEY', 'api_key');
define('WP_API_CODEIA_AUTH_APP_PASSWORD', 'app_password');

// Token types.
define('WP_API_CODEIA_TOKEN_ACCESS', 'access');
define('WP_API_CODEIA_TOKEN_REFRESH', 'refresh');

// JWT defaults.
define('WP_API_CODEIA_JWT_ALGORITHM', 'RS256');
define('WP_API_CODEIA_JWT_ACCESS_TTL', 3600); // 1 hour
define('WP_API_CODEIA_JWT_REFRESH_TTL', 2592000); // 30 days
define('WP_API_CODEIA_JWT_LEASE_TTL', 300); // 5 minutes
define('WP_API_CODEIA_JWT_ISSUER', 'wp-api-codeia');
define('WP_API_CODEIA_JWT_AUDIENCE', 'wp-api-v1');

// API Key prefix.
define('WP_API_CODEIA_API_KEY_PREFIX', 'wack');

// Cache keys.
define('WP_API_CODEIA_CACHE_GROUP', 'wp_api_codeia');

// Database table names.
define('WP_API_CODEIA_TOKENS_TABLE', 'codeia_tokens');
define('WP_API_CODEIA_API_KEYS_TABLE', 'codeia_api_keys');

// Error codes.
define('WP_API_CODEIA_ERROR_AUTH_MISSING', 'codeia_auth_missing');
define('WP_API_CODEIA_ERROR_AUTH_INVALID', 'codeia_auth_invalid');
define('WP_API_CODEIA_ERROR_AUTH_EXPIRED', 'codeia_auth_expired');
define('WP_API_CODEIA_ERROR_AUTH_REVOKED', 'codeia_auth_revoked');
define('WP_API_CODEIA_ERROR_FORBIDDEN', 'codeia_forbidden');
define('WP_API_CODEIA_ERROR_VALIDATION_FAILED', 'codeia_validation_failed');
define('WP_API_CODEIA_ERROR_MISSING_PARAM', 'codeia_missing_param');
define('WP_API_CODEIA_ERROR_INVALID_PARAM', 'codeia_invalid_param');
define('WP_API_CODEIA_ERROR_NOT_FOUND', 'codeia_not_found');
define('WP_API_CODEIA_ERROR_RATE_LIMITED', 'codeia_rate_limited');
define('WP_API_CODEIA_ERROR_SERVER_ERROR', 'codeia_server_error');
