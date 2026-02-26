<?php
/**
 * Authentication Strategy Interface
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Auth\Strategies;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Authentication Strategy Interface.
 *
 * Defines the contract for authentication strategies that work
 * with pre-extracted credentials arrays.
 *
 * This is separate from the REST-specific authentication interface
 * which works with WP_REST_Request objects.
 *
 * @since 1.0.0
 */
interface AuthStrategyInterface
{
    /**
     * Check if strategy supports the credentials.
     *
     * @since 1.0.0
     *
     * @param array $credentials Credentials array.
     * @return bool
     */
    public function supports(array $credentials);

    /**
     * Authenticate user with credentials.
     *
     * @since 1.0.0
     *
     * @param array $credentials Credentials array.
     * @return \WP_User|\WP_Error
     */
    public function authenticate(array $credentials);

    /**
     * Challenge for authentication (WWW-Authenticate header).
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getChallenge();
}
