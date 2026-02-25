<?php
/**
 * App Password Authentication Strategy
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
 * App Password Authentication Strategy.
 *
 * Implements WordPress application password authentication.
 * Uses WordPress built-in application passwords feature if available,
 * otherwise provides a fallback implementation.
 *
 * @since 1.0.0
 */
class AppPasswordStrategy implements AuthStrategyInterface
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
     * Use WordPress native application passwords.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    protected $useNative = true;

    /**
     * Create a new App Password Strategy instance.
     *
     * @since 1.0.0
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;

        // Check if WordPress has native application passwords (WP 5.6+)
        $this->useNative = function_exists('wp_get_application_passwords');
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
        return isset($credentials['type']) && $credentials['type'] === WP_API_CODEIA_AUTH_APP_PASSWORD
            && isset($credentials['username'])
            && isset($credentials['password']);
    }

    /**
     * Authenticate user with username and app password.
     *
     * @since 1.0.0
     *
     * @param array $credentials Credentials with 'username' and 'password'.
     * @return \WP_User|\WP_Error
     */
    public function authenticate(array $credentials)
    {
        $username = $credentials['username'];
        $password = $credentials['password'];

        if (empty($username) || empty($password)) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_MISSING,
                'Username and password required',
                array('status' => 401)
            );
        }

        // Get user by username or email
        $user = get_user_by('login', $username);

        if (!$user) {
            $user = get_user_by('email', $username);
        }

        if (!$user) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid credentials',
                array('status' => 401)
            );
        }

        // Verify application password
        if ($this->useNative) {
            return $this->authenticateWithNative($user, $password);
        }

        return $this->authenticateWithCustom($user, $password);
    }

    /**
     * Authenticate using WordPress native application passwords.
     *
     * @since 1.0.0
     *
     * @param \WP_User $user     User object.
     * @param string   $password Application password.
     * @return \WP_User|\WP_Error
     */
    protected function authenticateWithNative($user, $password)
    {
        $result = wp_verify_application_password($user);

        if ($result === false) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid application password',
                array('status' => 401)
            );
        }

        // Check if the provided password matches any stored app password
        // WordPress handles this internally, but we need to verify the current request
        $appPasswords = wp_get_application_passwords($user->ID);
        $valid = false;

        foreach ($appPasswords as $appPassword) {
            if (wp_check_password($password, $appPassword['password'], $user->ID)) {
                $valid = true;
                break;
            }
        }

        if (!$valid) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid application password',
                array('status' => 401)
            );
        }

        $this->logger->info('App password authentication successful (native)', array(
            'user_id' => $user->ID,
        ));

        return $user;
    }

    /**
     * Authenticate using custom implementation.
     *
     * @since 1.0.0
     *
     * @param \WP_User $user     User object.
     * @param string   $password Application password.
     * @return \WP_User|\WP_Error
     */
    protected function authenticateWithCustom($user, $password)
    {
        $appPasswords = $this->getUserAppPasswords($user->ID);
        $valid = false;

        foreach ($appPasswords as $appPassword) {
            if (wp_check_password($password, $appPassword['password'], $user->ID)) {
                $valid = true;

                // Update last used
                $this->updateLastUsed($appPassword['app_password_id']);

                break;
            }
        }

        if (!$valid) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid application password',
                array('status' => 401)
            );
        }

        $this->logger->info('App password authentication successful (custom)', array(
            'user_id' => $user->ID,
        ));

        return $user;
    }

    /**
     * Get application passwords for user.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return array Array of app passwords.
     */
    protected function getUserAppPasswords($user_id)
    {
        global $wpdb;

        // Check if native table exists
        $table = $wpdb->base_prefix . 'application_passwords';

        $tableExists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");

        if ($tableExists) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC",
                $user_id
            ), ARRAY_A);
        }

        // Fallback to user meta storage
        $passwords = get_user_meta($user_id, 'codeia_app_passwords', true);

        return is_array($passwords) ? $passwords : array();
    }

    /**
     * Update last used timestamp.
     *
     * @since 1.0.0
     *
     * @param int $app_password_id App password ID.
     * @return bool
     */
    protected function updateLastUsed($app_password_id)
    {
        global $wpdb;

        $table = $wpdb->base_prefix . 'application_passwords';

        $tableExists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");

        if ($tableExists) {
            $updated = $wpdb->update(
                $table,
                array('last_used' => current_time('mysql')),
                array('uuid' => $app_password_id),
                array('%s'),
                array('%s')
            );

            return $updated !== false;
        }

        return true;
    }

    /**
     * Generate a new application password.
     *
     * @since 1.0.0
     *
     * @param int    $user_id   User ID.
     * @param string $name      Password name.
     * @param array  $args      Additional arguments.
     * @return string|\WP_Error Generated password or error.
     */
    public function generate($user_id, $name, $args = array())
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return new \WP_Error(
                'invalid_user',
                'User not found'
            );
        }

        if ($this->useNative) {
            return $this->generateWithNative($user, $name, $args);
        }

        return $this->generateWithCustom($user, $name, $args);
    }

    /**
     * Generate using WordPress native function.
     *
     * @since 1.0.0
     *
     * @param \WP_User $user User object.
     * @param string   $name Password name.
     * @param array    $args Additional arguments.
     * @return string|\WP_Error
     */
    protected function generateWithNative($user, $name, $args)
    {
        $result = WP_Application_Passwords::create_new_application_password($user, array('name' => $name));

        if (is_wp_error($result)) {
            return $result;
        }

        $this->logger->info('App password generated (native)', array(
            'user_id' => $user->ID,
            'name' => $name,
        ));

        return $result[0];
    }

    /**
     * Generate using custom implementation.
     *
     * @since 1.0.0
     *
     * @param \WP_User $user User object.
     * @param string   $name Password name.
     * @param array    $args Additional arguments.
     * @return string|\WP_Error
     */
    protected function generateWithCustom($user, $name, $args)
    {
        $password = wp_generate_password(24, false, false);
        $hashed = wp_hash_password($password);
        $uuid = wp_generate_uuid4();

        $appPassword = array(
            'uuid' => $uuid,
            'app_password_id' => $uuid,
            'password' => $hashed,
            'name' => $name,
            'created_at' => current_time('mysql'),
            'last_used' => null,
            'last_ip' => null,
        );

        // Store in user meta
        $passwords = $this->getUserAppPasswords($user->ID);
        $passwords[] = $appPassword;

        update_user_meta($user->ID, 'codeia_app_passwords', $passwords);

        $this->logger->info('App password generated (custom)', array(
            'user_id' => $user->ID,
            'name' => $name,
        ));

        return $password;
    }

    /**
     * Revoke an application password.
     *
     * @since 1.0.0
     *
     * @param int    $user_id User ID.
     * @param string $uuid    App password UUID.
     * @return bool
     */
    public function revoke($user_id, $uuid)
    {
        if ($this->useNative) {
            $deleted = WP_Application_Passwords::delete_application_password($user_id, $uuid);
        } else {
            $passwords = $this->getUserAppPasswords($user_id);
            $filtered = array_filter($passwords, function ($p) use ($uuid) {
                return $p['uuid'] !== $uuid && $p['app_password_id'] !== $uuid;
            });

            $deleted = count($passwords) !== count($filtered);

            if ($deleted) {
                update_user_meta($user_id, 'codeia_app_passwords', array_values($filtered));
            }
        }

        $this->logger->info('App password revoked', array(
            'user_id' => $user_id,
            'uuid' => $uuid,
        ));

        return $deleted;
    }

    /**
     * Revoke all application passwords for a user.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return int Number of passwords revoked.
     */
    public function revokeAll($user_id)
    {
        if ($this->useNative) {
            $count = WP_Application_Passwords::delete_all_application_passwords($user_id);
        } else {
            $passwords = $this->getUserAppPasswords($user_id);
            $count = count($passwords);

            update_user_meta($user_id, 'codeia_app_passwords', array());
        }

        $this->logger->info('All app passwords revoked', array(
            'user_id' => $user_id,
            'count' => $count,
        ));

        return $count;
    }

    /**
     * Get application passwords for a user.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return array Array of app passwords.
     */
    public function getUserPasswords($user_id)
    {
        $passwords = $this->getUserAppPasswords($user_id);

        // Remove the actual password hash from output
        return array_map(function ($p) {
            unset($p['password']);
            return $p;
        }, $passwords);
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
        return 'Basic realm="' . get_bloginfo('url') . '"';
    }

    /**
     * Revoke tokens (for logout).
     *
     * @since 1.0.0
     *
     * @param \WP_User $user User object.
     * @return bool
     */
    public function revokeTokens($user)
    {
        // For app passwords, we don't revoke on logout
        // Users manage their app passwords manually
        return true;
    }
}
