<?php
/**
 * Authentication Controller
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Auth\Controllers;

use WP_API_Codeia\Auth\Manager;
use WP_API_Codeia\Utils\Logger\Logger;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Authentication REST API Controller.
 *
 * Handles authentication endpoints:
 * - POST /auth/login
 * - POST /auth/refresh
 * - POST /auth/logout
 * - GET /auth/verify
 *
 * @since 1.0.0
 */
class AuthController extends WP_REST_Controller
{
    /**
     * Authentication Manager.
     *
     * @since 1.0.0
     *
     * @var Manager
     */
    protected $authManager;

    /**
     * Logger instance.
     *
     * @since 1.0.0
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Namespace.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $namespace = WP_API_CODEIA_API_NAMESPACE . '/v1';

    /**
     * REST base.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $rest_base = 'auth';

    /**
     * Create a new Auth Controller instance.
     *
     * @since 1.0.0
     *
     * @param Manager $authManager Authentication Manager.
     * @param Logger  $logger      Logger instance.
     */
    public function __construct(Manager $authManager, Logger $logger)
    {
        $this->authManager = $authManager;
        $this->logger = $logger;
    }

    /**
     * Register the routes for the controller.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/login',
            array(
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this, 'login'),
                    'permission_callback' => '__return_true',
                    'args' => $this->getLoginEndpointArgs(),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/refresh',
            array(
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this, 'refresh'),
                    'permission_callback' => '__return_true',
                    'args' => array(
                        'refresh_token' => array(
                            'required' => true,
                            'type' => 'string',
                            'description' => 'Refresh token',
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/logout',
            array(
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this, 'logout'),
                    'permission_callback' => array($this, 'authenticateEndpoint'),
                    'args' => array(),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/verify',
            array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'verify'),
                    'permission_callback' => array($this, 'authenticateEndpoint'),
                    'args' => array(),
                ),
            )
        );

        // Register custom authentication method endpoint
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/me',
            array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'getCurrentUser'),
                    'permission_callback' => array($this, 'authenticateEndpoint'),
                    'args' => array(),
                ),
            )
        );
    }

    /**
     * Login endpoint.
     *
     * POST /auth/login
     *
     * Supports multiple authentication methods:
     * - JWT: username + password
     * - API Key: api_key
     * - App Password: username + app_password
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request Full request object.
     * @return WP_REST_Response|WP_Error
     */
    public function login(WP_REST_Request $request)
    {
        $strategy = $request->get_param('strategy');
        $username = $request->get_param('username');
        $password = $request->get_param('password');
        $apiKey = $request->get_param('api_key');

        // Auto-detect strategy if not specified
        if (empty($strategy)) {
            if (!empty($apiKey)) {
                $strategy = WP_API_CODEIA_AUTH_API_KEY;
            } elseif (!empty($username) && !empty($password)) {
                $strategy = WP_API_CODEIA_AUTH_JWT;
            } else {
                return new WP_Error(
                    WP_API_CODEIA_ERROR_VALIDATION_FAILED,
                    'Authentication credentials not provided',
                    array('status' => 400)
                );
            }
        }

        // Validate strategy
        $validStrategies = array(
            WP_API_CODEIA_AUTH_JWT,
            WP_API_CODEIA_AUTH_API_KEY,
            WP_API_CODEIA_AUTH_APP_PASSWORD,
        );

        if (!in_array($strategy, $validStrategies, true)) {
            return new WP_Error(
                WP_API_CODEIA_ERROR_VALIDATION_FAILED,
                'Invalid authentication strategy',
                array('status' => 400)
            );
        }

        $user = null;
        $credentials = array('type' => $strategy);

        switch ($strategy) {
            case WP_API_CODEIA_AUTH_JWT:
                if (empty($username) || empty($password)) {
                    return new WP_Error(
                        WP_API_CODEIA_ERROR_VALIDATION_FAILED,
                        'Username and password required',
                        array('status' => 400)
                    );
                }

                // Verify user credentials
                $user = wp_authenticate($username, $password);

                if (is_wp_error($user)) {
                    $this->logger->debug('Login failed', array(
                        'username' => $username,
                        'error' => $user->get_error_message(),
                    ));

                    return new WP_Error(
                        WP_API_CODEIA_ERROR_AUTH_INVALID,
                        'Invalid credentials',
                        array('status' => 401)
                    );
                }
                break;

            case WP_API_CODEIA_AUTH_API_KEY:
                if (empty($apiKey)) {
                    return new WP_Error(
                        WP_API_CODEIA_ERROR_VALIDATION_FAILED,
                        'API key required',
                        array('status' => 400)
                    );
                }

                $credentials['api_key'] = $apiKey;

                $strategyInstance = $this->authManager->getStrategy(WP_API_CODEIA_AUTH_API_KEY);

                if (!$strategyInstance) {
                    return new WP_Error(
                        WP_API_CODEIA_ERROR_AUTH_INVALID,
                        'API key authentication not available',
                        array('status' => 500)
                    );
                }

                $user = $strategyInstance->authenticate($credentials);

                if (is_wp_error($user)) {
                    return $user;
                }
                break;

            case WP_API_CODEIA_AUTH_APP_PASSWORD:
                if (empty($username) || empty($password)) {
                    return new WP_Error(
                        WP_API_CODEIA_ERROR_VALIDATION_FAILED,
                        'Username and password required',
                        array('status' => 400)
                    );
                }

                $credentials['username'] = $username;
                $credentials['password'] = $password;

                $strategyInstance = $this->authManager->getStrategy(WP_API_CODEIA_AUTH_APP_PASSWORD);

                if (!$strategyInstance) {
                    return new WP_Error(
                        WP_API_CODEIA_ERROR_AUTH_INVALID,
                        'App password authentication not available',
                        array('status' => 500)
                    );
                }

                $user = $strategyInstance->authenticate($credentials);

                if (is_wp_error($user)) {
                    return $user;
                }
                break;
        }

        if (!$user || is_wp_error($user)) {
            return new WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Authentication failed',
                array('status' => 401)
            );
        }

        // Generate auth response
        $response = $this->authManager->generateAuthResponse($user, $strategy);

        return $this->sendResponse($response, __('Login successful', 'wp-api-codeia'));
    }

    /**
     * Refresh token endpoint.
     *
     * POST /auth/refresh
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request Full request object.
     * @return WP_REST_Response|WP_Error
     */
    public function refresh(WP_REST_Request $request)
    {
        $refreshToken = $request->get_param('refresh_token');

        if (empty($refreshToken)) {
            return new WP_Error(
                WP_API_CODEIA_ERROR_VALIDATION_FAILED,
                'Refresh token required',
                array('status' => 400)
            );
        }

        $result = $this->authManager->refreshToken($refreshToken);

        if (is_wp_error($result)) {
            return $result;
        }

        return $this->sendResponse($result, __('Token refreshed successfully', 'wp-api-codeia'));
    }

    /**
     * Logout endpoint.
     *
     * POST /auth/logout
     *
     * Revokes the current user's tokens.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request Full request object.
     * @return WP_REST_Response|WP_Error
     */
    public function logout(WP_REST_Request $request)
    {
        $user = wp_get_current_user();

        if (!$user || !$user->exists()) {
            return new WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Not authenticated',
                array('status' => 401)
            );
        }

        $strategy = $request->get_header('x_auth_strategy');

        if (empty($strategy)) {
            $strategy = WP_API_CODEIA_AUTH_JWT;
        }

        $result = $this->authManager->logout($user, $strategy);

        if (!$result) {
            return new WP_Error(
                'logout_failed',
                'Failed to logout',
                array('status' => 500)
            );
        }

        return $this->sendResponse(
            array('message' => 'Logged out successfully'),
            __('Logout successful', 'wp-api-codeia')
        );
    }

    /**
     * Verify authentication endpoint.
     *
     * GET /auth/verify
     *
     * Returns current user info if authenticated.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request Full request object.
     * @return WP_REST_Response|WP_Error
     */
    public function verify(WP_REST_Request $request)
    {
        $user = wp_get_current_user();

        if (!$user || !$user->exists()) {
            return new WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Not authenticated',
                array('status' => 401)
            );
        }

        return $this->sendResponse(
            array(
                'authenticated' => true,
                'user' => array(
                    'id' => $user->ID,
                    'username' => $user->user_login,
                    'email' => $user->user_email,
                    'roles' => $user->roles,
                ),
            ),
            __('Authentication verified', 'wp-api-codeia')
        );
    }

    /**
     * Get current user endpoint.
     *
     * GET /auth/me
     *
     * Returns detailed current user info.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request Full request object.
     * @return WP_REST_Response|WP_Error
     */
    public function getCurrentUser(WP_REST_Request $request)
    {
        $user = wp_get_current_user();

        if (!$user || !$user->exists()) {
            return new WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Not authenticated',
                array('status' => 401)
            );
        }

        $data = array(
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'roles' => $user->roles,
            'capabilities' => $this->getUserCapabilities($user),
            'meta' => $this->getUserMeta($user->ID),
        );

        return $this->sendResponse($data);
    }

    /**
     * Authentication callback for endpoints.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $request Full request object.
     * @return bool|\WP_Error
     */
    public function authenticateEndpoint(WP_REST_Request $request)
    {
        $user = wp_get_current_user();

        if (!$user || !$user->exists()) {
            return new WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Authentication required',
                array('status' => 401)
            );
        }

        return true;
    }

    /**
     * Get user capabilities.
     *
     * @since 1.0.0
     *
     * @param \WP_User $user User object.
     * @return array
     */
    protected function getUserCapabilities($user)
    {
        $allCaps = array_keys($user->allcaps);

        // Filter out meta capabilities
        $metaCaps = array_filter($allCaps, function ($cap) {
            return strpos($cap, 'level_') === 0;
        });

        return array_values(array_diff($allCaps, $metaCaps));
    }

    /**
     * Get user meta (filtered).
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return array
     */
    protected function getUserMeta($user_id)
    {
        $allMeta = get_user_meta($user_id);

        // Filter sensitive meta
        $protectedKeys = array(
            'session_tokens',
            'codeia_app_passwords',
            'wp_capabilities',
            'wp_user_level',
        );

        $meta = array();

        foreach ($allMeta as $key => $values) {
            if (!in_array($key, $protectedKeys, true)) {
                $meta[$key] = count($values) > 1 ? $values : $values[0];
            }
        }

        return $meta;
    }

    /**
     * Get login endpoint arguments.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getLoginEndpointArgs()
    {
        return array(
            'username' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Username or email',
            ),
            'password' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'User password',
            ),
            'api_key' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'API key for authentication',
            ),
            'strategy' => array(
                'required' => false,
                'type' => 'string',
                'description' => 'Authentication strategy (jwt, api_key, app_password)',
                'enum' => array(WP_API_CODEIA_AUTH_JWT, WP_API_CODEIA_AUTH_API_KEY, WP_API_CODEIA_AUTH_APP_PASSWORD),
            ),
        );
    }

    /**
     * Send formatted response.
     *
     * @since 1.0.0
     *
     * @param array  $data    Response data.
     * @param string $message Optional message.
     * @return WP_REST_Response
     */
    protected function sendResponse($data, $message = '')
    {
        $response = array(
            'success' => true,
            'data' => $data,
            'meta' => array(
                'timestamp' => current_time('mysql'),
                'request_id' => wp_generate_uuid4(),
                'version' => WP_API_CODEIA_API_VERSION,
            ),
        );

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return rest_ensure_response($response);
    }

    /**
     * Send error response.
     *
     * @since 1.0.0
     *
     * @param string|\WP_Error $error   Error code or WP_Error.
     * @param string           $message Error message.
     * @param int              $status  HTTP status code.
     * @return WP_Error
     */
    protected function sendError($error, $message = '', $status = 400)
    {
        if (is_wp_error($error)) {
            return $error;
        }

        return new WP_Error(
            $error,
            $message,
            array('status' => $status)
        );
    }
}
