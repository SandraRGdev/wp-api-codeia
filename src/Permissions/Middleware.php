<?php
/**
 * Authorization Middleware
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Permissions;

use WP_API_Codeia\Permissions\Manager;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Authorization Middleware.
 *
 * Checks permissions before allowing access to endpoints.
 *
 * @since 1.0.0
 */
class Middleware
{
    /**
     * Permissions Manager instance.
     *
     * @since 1.0.0
     *
     * @var Manager
     */
    protected $manager;

    /**
     * Rate limiter instance.
     *
     * @since 1.0.0
     *
     * @var RateLimiter
     */
    protected $rateLimiter;

    /**
     * Create a new Middleware instance.
     *
     * @since 1.0.0
     *
     * @param Manager     $manager     Permissions Manager.
     * @param RateLimiter $rateLimiter Rate Limiter.
     */
    public function __construct(Manager $manager, RateLimiter $rateLimiter)
    {
        $this->manager = $manager;
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Check read permission.
     *
     * @since 1.0.0
     *
     * @param int    $user_id  User ID.
     * @param string $postType Post type.
     * @return bool|\WP_Error
     */
    public function canRead($user_id, $postType)
    {
        if ($this->manager->hasPermission($user_id, $postType, 'read')) {
            return true;
        }

        return new \WP_Error(
            WP_API_CODEIA_ERROR_FORBIDDEN,
            __('You do not have permission to read this resource', 'wp-api-codeia'),
            array('status' => 403)
        );
    }

    /**
     * Check create permission.
     *
     * @since 1.0.0
     *
     * @param int    $user_id  User ID.
     * @param string $postType Post type.
     * @return bool|\WP_Error
     */
    public function canCreate($user_id, $postType)
    {
        if ($this->manager->hasPermission($user_id, $postType, 'create')) {
            return true;
        }

        return new \WP_Error(
            WP_API_CODEIA_ERROR_FORBIDDEN,
            __('You do not have permission to create this resource', 'wp-api-codeia'),
            array('status' => 403)
        );
    }

    /**
     * Check update permission.
     *
     * @since 1.0.0
     *
     * @param int    $user_id  User ID.
     * @param string $postType Post type.
     * @param int    $item_id  Item ID.
     * @return bool|\WP_Error
     */
    public function canUpdate($user_id, $postType, $item_id)
    {
        if ($this->manager->hasPermission($user_id, $postType, 'update', $item_id)) {
            return true;
        }

        return new \WP_Error(
            WP_API_CODEIA_ERROR_FORBIDDEN,
            __('You do not have permission to update this resource', 'wp-api-codeia'),
            array('status' => 403)
        );
    }

    /**
     * Check delete permission.
     *
     * @since 1.0.0
     *
     * @param int    $user_id  User ID.
     * @param string $postType Post type.
     * @param int    $item_id  Item ID.
     * @return bool|\WP_Error
     */
    public function canDelete($user_id, $postType, $item_id)
    {
        if ($this->manager->hasPermission($user_id, $postType, 'delete', $item_id)) {
            return true;
        }

        return new \WP_Error(
            WP_API_CODEIA_ERROR_FORBIDDEN,
            __('You do not have permission to delete this resource', 'wp-api-codeia'),
            array('status' => 403)
        );
    }

    /**
     * Check rate limit.
     *
     * @since 1.0.0
     *
     * @param int    $user_id User ID.
     * @param string $action Action being performed.
     * @return bool|\WP_Error
     */
    public function checkRateLimit($user_id, $action = 'read')
    {
        $rateLimit = $this->manager->getRateLimit($user_id);

        $result = $this->rateLimiter->check($user_id, $rateLimit['limit'], $rateLimit['window']);

        if (is_wp_error($result)) {
            return $result;
        }

        return true;
    }

    /**
     * Filter response fields by permissions.
     *
     * @since 1.0.0
     *
     * @param array $data    Response data.
     * @param int   $user_id User ID.
     * @return array
     */
    public function filterResponseFields($data, $user_id)
    {
        if (!is_array($data)) {
            return $data;
        }

        $filtered = array();

        foreach ($data as $key => $value) {
            if ($this->manager->canReadField($key, $user_id)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Filter request fields by permissions.
     *
     * @since 1.0.0
     *
     * @param array $data    Request data.
     * @param int   $user_id User ID.
     * @return array
     */
    public function filterRequestFields($data, $user_id)
    {
        // For requests, we check if user can modify the field
        // For now, same as read permission
        return $this->filterResponseFields($data, $user_id);
    }

    /**
     * Get permission error.
     *
     * @since 1.0.0
     *
     * @param string $message Error message.
     * @return \WP_Error
     */
    public function forbiddenError($message = '')
    {
        if (empty($message)) {
            $message = __('You do not have permission to perform this action', 'wp-api-codeia');
        }

        return new \WP_Error(
            WP_API_CODEIA_ERROR_FORBIDDEN,
            $message,
            array('status' => 403)
        );
    }
}
