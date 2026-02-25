<?php
/**
 * Permissions Manager
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Permissions;

use WP_API_Codeia\Core\Interfaces\ServiceInterface;
use WP_API_Codeia\Utils\Cache\CacheManager;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Permissions Manager.
 *
 * Manages role-based permissions for API access including
 * endpoint permissions, field permissions, and ownership checks.
 *
 * @since 1.0.0
 */
class Manager implements ServiceInterface
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
     * Permissions matrix.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $matrix = array();

    /**
     * Field permissions.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $fieldPermissions = array();

    /**
     * Option key for permissions storage.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $optionKey = 'codeia_permissions';

    /**
     * Create a new Permissions Manager instance.
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
     * Register the permissions service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        $this->loadPermissions();
    }

    /**
     * Boot the permissions service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        add_action('wp_api_codeia_permissions_changed', array($this, 'clearCache'));
    }

    /**
     * Load permissions from storage.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function loadPermissions()
    {
        $cached = $this->cache->get('codeia_permissions_matrix');

        if ($cached !== null) {
            $this->matrix = $cached;
            return;
        }

        $stored = get_option($this->optionKey, array());

        $this->matrix = $this->mergeWithDefaults($stored);
        $this->cache->set('codeia_permissions_matrix', $this->matrix, 3600);
    }

    /**
     * Get default permissions.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getDefaultPermissions()
    {
        return array(
            'roles' => array(
                'administrator' => $this->getAdminPermissions(),
                'editor' => $this->getEditorPermissions(),
                'author' => $this->getAuthorPermissions(),
                'contributor' => $this->getContributorPermissions(),
                'subscriber' => $this->getSubscriberPermissions(),
            ),
            'fields' => array(
                'allowed' => array('*'),
                'denied' => array('user_pass', 'user_activation_key'),
            ),
            'rate_limits' => array(
                'default' => array(
                    'limit' => 1000,
                    'window' => 3600,
                ),
            ),
        );
    }

    /**
     * Get administrator permissions.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getAdminPermissions()
    {
        return array(
            '*' => array(
                'read' => true,
                'create' => true,
                'update' => true,
                'delete' => true,
            ),
        );
    }

    /**
     * Get editor permissions.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getEditorPermissions()
    {
        return array(
            'post' => array(
                'read' => true,
                'create' => true,
                'update' => true,
                'delete' => true,
                'own_only' => false,
            ),
            'page' => array(
                'read' => true,
                'create' => true,
                'update' => true,
                'delete' => true,
                'own_only' => false,
            ),
            'media' => array(
                'read' => true,
                'create' => true,
                'update' => true,
                'delete' => false,
            ),
        );
    }

    /**
     * Get author permissions.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getAuthorPermissions()
    {
        return array(
            'post' => array(
                'read' => true,
                'create' => true,
                'update' => true,
                'delete' => false,
                'own_only' => true,
            ),
            'media' => array(
                'read' => true,
                'create' => true,
                'update' => true,
                'delete' => false,
                'own_only' => true,
            ),
        );
    }

    /**
     * Get contributor permissions.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getContributorPermissions()
    {
        return array(
            'post' => array(
                'read' => true,
                'create' => true,
                'update' => true,
                'delete' => false,
                'own_only' => true,
            ),
        );
    }

    /**
     * Get subscriber permissions.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getSubscriberPermissions()
    {
        return array(
            'post' => array(
                'read' => true,
                'create' => false,
                'update' => false,
                'delete' => false,
            ),
            'page' => array(
                'read' => true,
                'create' => false,
                'update' => false,
                'delete' => false,
            ),
        );
    }

    /**
     * Merge stored permissions with defaults.
     *
     * @since 1.0.0
     *
     * @param array $stored Stored permissions.
     * @return array
     */
    protected function mergeWithDefaults($stored)
    {
        $defaults = $this->getDefaultPermissions();

        if (empty($stored)) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $stored);
    }

    /**
     * Check if user has permission.
     *
     * @since 1.0.0
     *
     * @param int    $user_id  User ID.
     * @param string $postType Post type.
     * @param string $action  Action (read, create, update, delete).
     * @param int    $item_id Item ID for ownership check.
     * @return bool
     */
    public function hasPermission($user_id, $postType, $action, $item_id = 0)
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        // Check wildcard permission
        if ($this->checkWildcardPermission($user->roles, $postType, $action)) {
            return true;
        }

        // Check role permissions
        foreach ($user->roles as $role) {
            if ($this->checkRolePermission($role, $postType, $action, $user_id, $item_id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check wildcard permission.
     *
     * @since 1.0.0
     *
     * @param array  $roles    User roles.
     * @param string $postType Post type.
     * @param string $action  Action.
     * @return bool
     */
    protected function checkWildcardPermission($roles, $postType, $action)
    {
        foreach ($roles as $role) {
            if (isset($this->matrix['roles'][$role]['*'][$action])) {
                return (bool) $this->matrix['roles'][$role]['*'][$action];
            }
        }

        return false;
    }

    /**
     * Check role permission.
     *
     * @since 1.0.0
     *
     * @param string $role     Role slug.
     * @param string $postType Post type.
     * @param string $action  Action.
     * @param int    $user_id  User ID.
     * @param int    $item_id  Item ID.
     * @return bool
     */
    protected function checkRolePermission($role, $postType, $action, $user_id = 0, $item_id = 0)
    {
        if (!isset($this->matrix['roles'][$role])) {
            return false;
        }

        $rolePerms = $this->matrix['roles'][$role];

        // Check wildcard first
        if (isset($rolePerms['*'][$action]) && $rolePerms['*'][$action]) {
            return true;
        }

        // Check specific post type
        if (!isset($rolePerms[$postType][$action])) {
            return false;
        }

        if (!$rolePerms[$postType][$action]) {
            return false;
        }

        // Check ownership restriction
        if (isset($rolePerms[$postType]['own_only']) && $rolePerms[$postType]['own_only']) {
            if ($item_id > 0) {
                $item = get_post($item_id);
                if ($item && $item->post_author != $user_id) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Check field permission.
     *
     * @since 1.0.0
     *
     * @param string $field Field name.
     * @param int    $user_id User ID.
     * @return bool
     */
    public function canReadField($field, $user_id)
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return false;
        }

        // Check denied fields
        if (in_array($field, $this->matrix['fields']['denied'], true)) {
            return false;
        }

        // Check allowed fields (if not wildcard)
        if (!in_array('*', $this->matrix['fields']['allowed'], true)) {
            if (!in_array($field, $this->matrix['fields']['allowed'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Filter fields by permissions.
     *
     * @since 1.0.0
     *
     * @param array $fields Fields to filter.
     * @param int   $user_id User ID.
     * @return array
     */
    public function filterFields($fields, $user_id)
    {
        return array_filter($fields, function ($field) use ($user_id) {
            return $this->canReadField($field, $user_id);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get role permissions.
     *
     * @since 1.0.0
     *
     * @param string $role Role slug.
     * @return array
     */
    public function getRolePermissions($role)
    {
        return isset($this->matrix['roles'][$role])
            ? $this->matrix['roles'][$role]
            : array();
    }

    /**
     * Set role permissions.
     *
     * @since 1.0.0
     *
     * @param string $role        Role slug.
     * @param array  $permissions Permissions array.
     * @return bool
     */
    public function setRolePermissions($role, $permissions)
    {
        $this->matrix['roles'][$role] = $permissions;

        return $this->savePermissions();
    }

    /**
     * Get field permissions.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getFieldPermissions()
    {
        return $this->matrix['fields'];
    }

    /**
     * Set field permissions.
     *
     * @since 1.0.0
     *
     * @param array $allowed Allowed fields.
     * @param array $denied  Denied fields.
     * @return bool
     */
    public function setFieldPermissions($allowed, $denied = array())
    {
        $this->matrix['fields']['allowed'] = $allowed;
        $this->matrix['fields']['denied'] = $denied;

        return $this->savePermissions();
    }

    /**
     * Get rate limit for user.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return array
     */
    public function getRateLimit($user_id)
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return $this->matrix['rate_limits']['default'];
        }

        // Check for role-specific rate limits
        foreach ($user->roles as $role) {
            if (isset($this->matrix['rate_limits'][$role])) {
                return $this->matrix['rate_limits'][$role];
            }
        }

        return $this->matrix['rate_limits']['default'];
    }

    /**
     * Set rate limit.
     *
     * @since 1.0.0
     *
     * @param string $role   Role or 'default'.
     * @param int    $limit  Request limit.
     * @param int    $window Time window in seconds.
     * @return bool
     */
    public function setRateLimit($role, $limit, $window)
    {
        $this->matrix['rate_limits'][$role] = array(
            'limit' => $limit,
            'window' => $window,
        );

        return $this->savePermissions();
    }

    /**
     * Save permissions to storage.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    protected function savePermissions()
    {
        $updated = update_option($this->optionKey, $this->matrix, false);

        if ($updated) {
            $this->cache->set('codeia_permissions_matrix', $this->matrix, 3600);
            do_action('wp_api_codeia_permissions_changed', $this->matrix);
        }

        return $updated;
    }

    /**
     * Clear permissions cache.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function clearCache()
    {
        $this->cache->delete('codeia_permissions_matrix');
    }

    /**
     * Reset permissions to defaults.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function resetToDefaults()
    {
        $this->matrix = $this->getDefaultPermissions();

        return delete_option($this->optionKey) && $this->savePermissions();
    }

    /**
     * Get complete permissions matrix.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getMatrix()
    {
        return $this->matrix;
    }

    /**
     * Export permissions as JSON.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function export()
    {
        return wp_json_encode($this->matrix, JSON_PRETTY_PRINT);
    }

    /**
     * Import permissions from JSON.
     *
     * @since 1.0.0
     *
     * @param string $json JSON string.
     * @return bool|\WP_Error
     */
    public function import($json)
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error(
                'invalid_json',
                __('Invalid JSON format', 'wp-api-codeia')
            );
        }

        if (!is_array($data)) {
            return new \WP_Error(
                'invalid_data',
                __('Invalid permissions data', 'wp-api-codeia')
            );
        }

        $this->matrix = $this->mergeWithDefaults($data);

        return $this->savePermissions();
    }
}
