<?php
/**
 * Rate Limiter
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Permissions;

use WP_API_Codeia\Utils\Cache\CacheManager;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rate Limiter.
 *
 * Implements rate limiting for API requests using various strategies.
 *
 * @since 1.0.0
 */
class RateLimiter
{
    /**
     * Cache Manager instance.
     *
     * @since 1.0.0
     *
     * @var CacheManager
     */
    protected $cache;

    /**
     * Logger instance.
     *
     * @since 1.0.0
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Create a new Rate Limiter instance.
     *
     * @since 1.0.0
     *
     * @param CacheManager $cache Cache Manager.
     * @param Logger       $logger Logger instance.
     */
    public function __construct(CacheManager $cache, Logger $logger)
    {
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Check rate limit for user.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID (0 for anonymous).
     * @param int $limit   Request limit.
     * @param int $window   Time window in seconds.
     * @return bool|\WP_Error
     */
    public function check($user_id, $limit, $window)
    {
        $key = $this->getCacheKey($user_id);
        $data = $this->cache->get($key);

        if ($data === null) {
            $data = array(
                'count' => 1,
                'reset' => time() + $window,
            );
            $this->cache->set($key, $data, $window);
            return true;
        }

        if (time() > $data['reset']) {
            // Window expired, reset
            $data = array(
                'count' => 1,
                'reset' => time() + $window,
            );
            $this->cache->set($key, $data, $window);
            return true;
        }

        if ($data['count'] >= $limit) {
            $retryAfter = $data['reset'] - time();

            $this->logger->debug('Rate limit exceeded', array(
                'user_id' => $user_id,
                'retry_after' => $retryAfter,
            ));

            return new \WP_Error(
                WP_API_CODEIA_ERROR_RATE_LIMITED,
                __('Rate limit exceeded', 'wp-api-codeia'),
                array(
                    'status' => 429,
                    'retry_after' => $retryAfter,
                )
            );
        }

        $data['count']++;
        $this->cache->set($key, $data, $window);

        return true;
    }

    /**
     * Get remaining requests.
     *
     * @since 1.0.0
     *
     * @param int    $user_id User ID.
     * @param int    $limit   Request limit.
     * @param int    $window   Time window in seconds.
     * @return array
     */
    public function getRemaining($user_id, $limit, $window)
    {
        $key = $this->getCacheKey($user_id);
        $data = $this->cache->get($key);

        if ($data === null) {
            return array(
                'remaining' => $limit,
                'reset' => time() + $window,
            );
        }

        if (time() > $data['reset']) {
            return array(
                'remaining' => $limit,
                'reset' => time() + $window,
            );
        }

        return array(
            'remaining' => max(0, $limit - $data['count']),
            'reset' => $data['reset'],
        );
    }

    /**
     * Reset rate limit for user.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return bool
     */
    public function reset($user_id)
    {
        $key = $this->getCacheKey($user_id);

        return $this->cache->delete($key);
    }

    /**
     * Get rate limit cache key.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return string
     */
    protected function getCacheKey($user_id)
    {
        return 'codeia_ratelimit_' . $user_id;
    }

    /**
     * Get rate limit headers for response.
     *
     * @since 1.0.0
     *
     * @param int    $user_id User ID.
     * @param int    $limit   Request limit.
     * @param int    $window   Time window in seconds.
     * @return array
     */
    public function getHeaders($user_id, $limit, $window)
    {
        $remaining = $this->getRemaining($user_id, $limit, $window);

        return array(
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => $remaining['remaining'],
            'X-RateLimit-Reset' => $remaining['reset'],
        );
    }

    /**
     * Check rate limit by IP address.
     *
     * @since 1.0.0
     *
     * @param string $ip    IP address.
     * @param int    $limit Request limit.
     * @param int    $window Time window in seconds.
     * @return bool|\WP_Error
     */
    public function checkByIP($ip, $limit, $window)
    {
        $key = 'codeia_ratelimit_ip_' . md5($ip);
        $data = $this->cache->get($key);

        if ($data === null) {
            $data = array(
                'count' => 1,
                'reset' => time() + $window,
            );
            $this->cache->set($key, $data, $window);
            return true;
        }

        if (time() > $data['reset']) {
            $data = array(
                'count' => 1,
                'reset' => time() + $window,
            );
            $this->cache->set($key, $data, $window);
            return true;
        }

        if ($data['count'] >= $limit) {
            $retryAfter = $data['reset'] - time();

            return new \WP_Error(
                WP_API_CODEIA_ERROR_RATE_LIMITED,
                __('Rate limit exceeded', 'wp-api-codeia'),
                array(
                    'status' => 429,
                    'retry_after' => $retryAfter,
                )
            );
        }

        $data['count']++;
        $this->cache->set($key, $data, $window);

        return true;
    }

    /**
     * Get client IP address.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getClientIP()
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
     * Check rate limit by authentication method.
     *
     * @since 1.0.0
     *
     * @param string $auth_method Auth method (jwt, api_key, etc).
     * @param int    $limit       Request limit.
     * @param int    $window      Time window in seconds.
     * @return bool|\WP_Error
     */
    public function checkByAuthMethod($auth_method, $limit, $window)
    {
        $key = 'codeia_ratelimit_auth_' . $auth_method;
        $data = $this->cache->get($key);

        if ($data === null) {
            $data = array(
                'count' => 1,
                'reset' => time() + $window,
            );
            $this->cache->set($key, $data, $window);
            return true;
        }

        if (time() > $data['reset']) {
            $data = array(
                'count' => 1,
                'reset' => time() + $window,
            );
            $this->cache->set($key, $data, $window);
            return true;
        }

        if ($data['count'] >= $limit) {
            $retryAfter = $data['reset'] - time();

            return new \WP_Error(
                WP_API_CODEIA_ERROR_RATE_LIMITED,
                __('Rate limit exceeded', 'wp-api-codeia'),
                array(
                    'status' => 429,
                    'retry_after' => $retryAfter,
                )
            );
        }

        $data['count']++;
        $this->cache->set($key, $data, $window);

        return true;
    }

    /**
     * Clean up expired rate limit entries.
     *
     * @since 1.0.0
     *
     * @return int Number of entries cleaned.
     */
    public function cleanup()
    {
        // Cache entries will expire automatically via TTL
        // This is a placeholder for any additional cleanup logic
        return 0;
    }
}
