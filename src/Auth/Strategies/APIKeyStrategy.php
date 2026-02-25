<?php
/**
 * API Key Authentication Strategy
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Auth\Strategies;

use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API Key Authentication Strategy.
 *
 * Implements API key authentication using the format:
 * wack_{site_id}_{user_id}_{random}_{checksum}
 *
 * @since 1.0.0
 */
class APIKeyStrategy implements AuthStrategyInterface
{
    /**
     * Logger instance.
     *
     * @since 1.0.0
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Cache of validated keys.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $cache = array();

    /**
     * Create a new API Key Strategy instance.
     *
     * @since 1.0.0
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Check if strategy supports the credentials.
     *
     * @since 1.0.0
     *
     * @param array $credentials Credentials array.
     * @return bool
     */
    public function supports(array $credentials)
    {
        return isset($credentials['type']) && $credentials['type'] === WP_API_CODEIA_AUTH_API_KEY
            && isset($credentials['api_key']);
    }

    /**
     * Authenticate user with API key.
     *
     * @since 1.0.0
     *
     * @param array $credentials Credentials array with 'api_key' key.
     * @return \WP_User|\WP_Error
     */
    public function authenticate(array $credentials)
    {
        $apiKey = $credentials['api_key'];

        if (empty($apiKey)) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_MISSING,
                'API key not provided',
                array('status' => 401)
            );
        }

        // Validate API key format
        if (!$this->isValidFormat($apiKey)) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid API key format',
                array('status' => 401)
            );
        }

        // Check cache first
        if (isset($this->cache[$apiKey])) {
            $cached = $this->cache[$apiKey];

            // Cache expires after 5 minutes
            if (time() - $cached['time'] < 300) {
                $user = get_userdata($cached['user_id']);

                if ($user) {
                    $this->updateLastUsed($apiKey);
                    return $user;
                }
            }

            unset($this->cache[$apiKey]);
        }

        // Get API key from database
        $keyData = $this->getKeyData($apiKey);

        if (!$keyData) {
            $this->logger->debug('API key not found in database', array(
                'key_prefix' => substr($apiKey, 0, 20) . '...',
            ));

            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid API key',
                array('status' => 401)
            );
        }

        // Check if revoked
        if (isset($keyData['is_revoked']) && $keyData['is_revoked']) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'API key has been revoked',
                array('status' => 401)
            );
        }

        // Check expiration
        if (!empty($keyData['expires_at'])) {
            $expires = strtotime($keyData['expires_at']);
            if ($expires < time()) {
                return new \WP_Error(
                    WP_API_CODEIA_ERROR_AUTH_EXPIRED,
                    'API key has expired',
                    array('status' => 401)
                );
            }
        }

        // Get user
        $user_id = isset($keyData['user_id']) ? intval($keyData['user_id']) : 0;

        if ($user_id <= 0) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid API key data',
                array('status' => 401)
            );
        }

        $user = get_userdata($user_id);

        if (!$user) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'User not found',
                array('status' => 401)
            );
        }

        // Cache the result
        $this->cache[$apiKey] = array(
            'user_id' => $user_id,
            'time' => time(),
        );

        // Update last used
        $this->updateLastUsed($apiKey);

        $this->logger->debug('API key authentication successful', array(
            'user_id' => $user_id,
            'key_id' => $keyData['api_key_id'],
        ));

        return $user;
    }

    /**
     * Check if API key has valid format.
     *
     * @since 1.0.0
     *
     * @param string $apiKey API key to check.
     * @return bool
     */
    protected function isValidFormat($apiKey)
    {
        $prefix = WP_API_CODEIA_API_KEY_PREFIX . '_';
        return strpos($apiKey, $prefix) === 0
            && strlen($apiKey) >= 40;
    }

    /**
     * Get API key data from database.
     *
     * @since 1.0.0
     *
     * @param string $apiKey API key.
     * @return array|null
     */
    protected function getKeyData($apiKey)
    {
        global $wpdb;

        $table = $wpdb->prefix . WP_API_CODEIA_API_KEYS_TABLE;

        $data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE api_key = %s",
            $apiKey
        ), ARRAY_A);

        return $data;
    }

    /**
     * Update last used timestamp and IP.
     *
     * @since 1.0.0
     *
     * @param string $apiKey API key.
     * @return bool
     */
    protected function updateLastUsed($apiKey)
    {
        global $wpdb;

        $table = $wpdb->prefix . WP_API_CODEIA_API_KEYS_TABLE;
        $ip = $this->getClientIP();

        $updated = $wpdb->update(
            $table,
            array(
                'last_used' => current_time('mysql'),
                'last_ip' => $ip,
            ),
            array('api_key' => $apiKey),
            array('%s', '%s'),
            array('%s')
        );

        return $updated !== false;
    }

    /**
     * Get client IP address.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected function getClientIP()
    {
        $ip = '';

        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_CF_CONNECTING_IP']);
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = explode(',', $ip)[0];
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = sanitize_text_field($_SERVER['HTTP_X_REAL_IP']);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }

        return $ip;
    }

    /**
     * Generate a new API key.
     *
     * @since 1.0.0
     *
     * @param int    $user_id  User ID.
     * @param string $name     Key name/description.
     * @param array  $scopes   Scopes for this key.
     * @param int    $expires  Expiration timestamp (0 for no expiration).
     * @return string|\WP_Error API key or error.
     */
    public function generate($user_id, $name, $scopes = array(), $expires = 0)
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return new \WP_Error(
                'invalid_user',
                'User not found'
            );
        }

        $site_id = get_current_blog_id();
        $random = wp_generate_password(32, false);
        $checksum = $this->calculateChecksum($site_id, $user_id, $random);

        $apiKey = sprintf(
            '%s_%d_%d_%s_%s',
            WP_API_CODEIA_API_KEY_PREFIX,
            $site_id,
            $user_id,
            $random,
            $checksum
        );

        // Store in database
        $stored = $this->storeKey($apiKey, $user_id, $name, $scopes, $expires);

        if (!$stored) {
            return new \WP_Error(
                'storage_error',
                'Failed to store API key'
            );
        }

        $this->logger->info('API key generated', array(
            'user_id' => $user_id,
            'name' => $name,
        ));

        return $apiKey;
    }

    /**
     * Calculate checksum for API key.
     *
     * @since 1.0.0
     *
     * @param int    $site_id Site ID.
     * @param int    $user_id User ID.
     * @param string $random  Random string.
     * @return string Checksum.
     */
    protected function calculateChecksum($site_id, $user_id, $random)
    {
        $data = $site_id . $user_id . $random . NONCE_KEY;
        return substr(wp_hash($data), 0, 8);
    }

    /**
     * Store API key in database.
     *
     * @since 1.0.0
     *
     * @param string $apiKey  API key.
     * @param int    $user_id User ID.
     * @param string $name    Key name.
     * @param array  $scopes  Scopes.
     * @param int    $expires Expiration timestamp.
     * @return bool
     */
    protected function storeKey($apiKey, $user_id, $name, $scopes, $expires)
    {
        global $wpdb;

        $table = $wpdb->prefix . WP_API_CODEIA_API_KEYS_TABLE;

        $data = array(
            'api_key' => $apiKey,
            'user_id' => $user_id,
            'name' => $name,
            'scopes' => json_encode($scopes),
            'created_at' => current_time('mysql'),
            'expires_at' => $expires > 0 ? date('Y-m-d H:i:s', $expires) : null,
        );

        $inserted = $wpdb->insert($table, $data);

        return $inserted !== false;
    }

    /**
     * Revoke an API key.
     *
     * @since 1.0.0
     *
     * @param string $apiKey API key to revoke.
     * @return bool
     */
    public function revoke($apiKey)
    {
        global $wpdb;

        $table = $wpdb->prefix . WP_API_CODEIA_API_KEYS_TABLE;

        $updated = $wpdb->update(
            $table,
            array('is_revoked' => 1),
            array('api_key' => $apiKey),
            array('%d'),
            array('%s')
        );

        // Remove from cache
        unset($this->cache[$apiKey]);

        $this->logger->info('API key revoked', array(
            'key_prefix' => substr($apiKey, 0, 20) . '...',
        ));

        return $updated !== false;
    }

    /**
     * Revoke all API keys for a user.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return int Number of keys revoked.
     */
    public function revokeUserKeys($user_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . WP_API_CODEIA_API_KEYS_TABLE;

        $updated = $wpdb->update(
            $table,
            array('is_revoked' => 1),
            array('user_id' => $user_id),
            array('%d'),
            array('%d')
        );

        // Clear cache for this user
        foreach ($this->cache as $key => $data) {
            if ($data['user_id'] === $user_id) {
                unset($this->cache[$key]);
            }
        }

        $this->logger->info('All API keys revoked for user', array(
            'user_id' => $user_id,
        ));

        return $updated;
    }

    /**
     * Get API keys for a user.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return array Array of API key data.
     */
    public function getUserKeys($user_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . WP_API_CODEIA_API_KEYS_TABLE;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT api_key_id, name, scopes, last_used, last_ip, created_at, expires_at, is_revoked
            FROM {$table}
            WHERE user_id = %d
            ORDER BY created_at DESC",
            $user_id
        ), ARRAY_A);

        return $results;
    }

    /**
     * Get WWW-Authenticate challenge.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getChallenge()
    {
        return 'Key realm="' . get_bloginfo('url') . '"';
    }

    /**
     * Check rate limit for API key.
     *
     * @since 1.0.0
     *
     * @param string $apiKey API key.
     * @return bool|\WP_Error True if within limit, error if exceeded.
     */
    public function checkRateLimit($apiKey)
    {
        global $wpdb;

        $table = $wpdb->prefix . WP_API_CODEIA_API_KEYS_TABLE;

        $keyData = $wpdb->get_row($wpdb->prepare(
            "SELECT rate_limit, rate_limit_window FROM {$table} WHERE api_key = %s",
            $apiKey
        ), ARRAY_A);

        if (!$keyData) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_RATE_LIMITED,
                'Invalid API key for rate limiting',
                array('status' => 429)
            );
        }

        $rateLimit = isset($keyData['rate_limit']) ? intval($keyData['rate_limit']) : 1000;
        $window = isset($keyData['rate_limit_window']) ? intval($keyData['rate_limit_window']) : 3600;

        $transientKey = 'codeia_ratelimit_' . md5($apiKey);
        $data = get_transient($transientKey);

        if ($data === false) {
            $data = array('count' => 1, 'reset' => time() + $window);
            set_transient($transientKey, $data, $window);
            return true;
        }

        if ($data['count'] >= $rateLimit) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_RATE_LIMITED,
                'Rate limit exceeded',
                array(
                    'status' => 429,
                    'retry_after' => $data['reset'] - time(),
                )
            );
        }

        $data['count']++;
        set_transient($transientKey, $data, $window);

        return true;
    }
}
