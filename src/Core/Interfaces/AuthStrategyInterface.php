<?php
/**
 * Authentication Strategy Interface
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Core\Interfaces;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for authentication strategies.
 *
 * @since 1.0.0
 */
interface AuthStrategyInterface
{
    /**
     * Authenticate the request.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request The REST request.
     * @return \WP_User|\WP_Error The authenticated user or error.
     */
    public function authenticate(\WP_REST_Request $request);

    /**
     * Validate credentials.
     *
     * @since 1.0.0
     *
     * @param mixed $credentials Credentials to validate.
     * @return bool True if valid, false otherwise.
     */
    public function validate($credentials);

    /**
     * Get the authentication scheme.
     *
     * @since 1.0.0
     *
     * @return string The authentication scheme (e.g., 'bearer', 'api_key').
     */
    public function getScheme();
}
