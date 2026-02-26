<?php
/**
 * Authentication Manager
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Auth;

use WP_API_Codeia\Core\Interfaces\ServiceInterface;
use WP_API_Codeia\Auth\Strategies\AuthStrategyInterface;
use WP_API_Codeia\Core\Container;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Authentication Manager.
 *
 * Coordinates authentication strategies and provides
 * a unified interface for authentication operations.
 *
 * @since 1.0.0
 */
class Manager implements ServiceInterface
{
    /**
     * Registered authentication strategies.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $strategies = array();

    /**
     * Default strategy.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $defaultStrategy = WP_API_CODEIA_AUTH_JWT;

    /**
     * Container instance.
     *
     * @since 1.0.0
     *
     * @var Container
     */
    protected $container;

    /**
     * Logger instance.
     *
     * @since 1.0.0
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Create a new Authentication Manager instance.
     *
     * @since 1.0.0
     *
     * @param Container $container DI Container.
     * @param Logger    $logger    Logger instance.
     */
    public function __construct(Container $container, Logger $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Register the authentication service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        // Register authentication strategies
        $this->registerDefaultStrategies();
    }

    /**
     * Boot the authentication service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        // TEMPORARILY DISABLED - This filter may be causing issues with core WP endpoints
        // TODO: Implement proper namespace checking
        // add_filter('rest_authentication_errors', array($this, 'authenticateRequest'));
    }

    /**
     * Register an authentication strategy.
     *
     * @since 1.0.0
     *
     * @param string                 $name     Strategy identifier.
     * @param AuthStrategyInterface $strategy Strategy instance.
     * @return self
     */
    public function registerStrategy($name, AuthStrategyInterface $strategy)
    {
        $this->strategies[$name] = $strategy;
        return $this;
    }

    /**
     * Register default authentication strategies.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function registerDefaultStrategies()
    {
        // Strategies will be registered via container
        // JWT, API Key, App Password
    }

    /**
     * Get a registered strategy.
     *
     * @since 1.0.0
     *
     * @param string $name Strategy name.
     * @return AuthStrategyInterface|null
     */
    public function getStrategy($name)
    {
        return isset($this->strategies[$name]) ? $this->strategies[$name] : null;
    }

    /**
     * Get all registered strategies.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getStrategies()
    {
        return $this->strategies;
    }

    /**
     * Set default strategy.
     *
     * @since 1.0.0
     *
     * @param string $strategy Strategy name.
     * @return self
     */
    public function setDefaultStrategy($strategy)
    {
        $this->defaultStrategy = $strategy;
        return $this;
    }

    /**
     * Get default strategy.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getDefaultStrategy()
    {
        return $this->defaultStrategy;
    }

    /**
     * Authenticate a request.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request|null $request Request object.
     * @return \WP_Error|\WP_User|null
     */
    public function authenticate($request = null)
    {
        if ($request === null) {
            $request = $this->getCurrentRequest();
        }

        if ($request === null) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_MISSING,
                'Authentication required',
                array('status' => 401)
            );
        }

        // Get credentials from request
        $credentials = $this->extractCredentials($request);

        if (empty($credentials)) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_MISSING,
                'Authentication credentials not provided',
                array('status' => 401)
            );
        }

        // Detect strategy from credentials
        $strategy = $this->detectStrategy($credentials);

        if ($strategy === null) {
            $this->logger->debug('No auth strategy detected for credentials', array(
                'type' => isset($credentials['type']) ? $credentials['type'] : 'unknown',
            ));

            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid authentication method',
                array('status' => 401)
            );
        }

        // Authenticate with strategy
        $result = $strategy->authenticate($credentials);

        if (is_wp_error($result)) {
            $this->logger->debug('Authentication failed', array(
                'strategy' => get_class($strategy),
                'error' => $result->get_error_message(),
            ));
        }

        return $result;
    }

    /**
     * Extract credentials from request.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return array
     */
    protected function extractCredentials($request)
    {
        $credentials = array();
        $headers = $request->get_headers();

        // Check Authorization header
        if (isset($headers['authorization'])) {
            $auth = $headers['authorization'];
            if (is_array($auth)) {
                $auth = reset($auth);
            }

            // Bearer token (JWT)
            if (preg_match('/^Bearer\s+(.+)$/', $auth, $matches)) {
                $credentials['type'] = WP_API_CODEIA_AUTH_JWT;
                $credentials['token'] = $matches[1];
                return $credentials;
            }

            // API Key
            if (preg_match('/^Key\s+(.+)$/', $auth, $matches)) {
                $credentials['type'] = WP_API_CODEIA_AUTH_API_KEY;
                $credentials['api_key'] = $matches[1];
                return $credentials;
            }
        }

        // Check for API Key in header
        if (isset($headers['x_api_key'])) {
            $key = $headers['x_api_key'];
            if (is_array($key)) {
                $key = reset($key);
            }
            $credentials['type'] = WP_API_CODEIA_AUTH_API_KEY;
            $credentials['api_key'] = $key;
            return $credentials;
        }

        // Check for Basic Auth (App Password)
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $credentials['type'] = WP_API_CODEIA_AUTH_APP_PASSWORD;
            $credentials['username'] = $_SERVER['PHP_AUTH_USER'];
            $credentials['password'] = $_SERVER['PHP_AUTH_PW'];
            return $credentials;
        }

        return $credentials;
    }

    /**
     * Detect authentication strategy from credentials.
     *
     * @since 1.0.0
     *
     * @param array $credentials Credentials array.
     * @return AuthStrategyInterface|null
     */
    protected function detectStrategy($credentials)
    {
        $type = isset($credentials['type']) ? $credentials['type'] : '';

        foreach ($this->strategies as $name => $strategy) {
            if ($strategy->supports($credentials)) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * Get current REST request.
     *
     * @since 1.0.0
     *
     * @return \WP_REST_Request|null
     */
    protected function getCurrentRequest()
    {
        if (did_action('rest_api_init')) {
            return rest_get_server()->get_current_request();
        }
        return null;
    }

    /**
     * REST API authentication filter.
     *
     * @since 1.0.0
     *
     * @param mixed $result Previous authentication result.
     * @return mixed
     */
    public function authenticateRequest($result)
    {
        // Skip if already authenticated or error
        if (!empty($result) || !is_null($result)) {
            return $result;
        }

        // Only authenticate our namespace
        $request = $this->getCurrentRequest();
        if ($request === null) {
            return $result;
        }

        $route = $request->get_route();
        $namespace = '/' . WP_API_CODEIA_API_NAMESPACE . '/';

        if (strpos($route, $namespace) !== 0) {
            return $result;
        }

        // Skip public endpoints
        $publicEndpoints = array(
            '/' . WP_API_CODEIA_API_NAMESPACE . '/v1/docs',
            '/' . WP_API_CODEIA_API_NAMESPACE . '/v1/docs/swagger',
        );

        if (in_array($route, $publicEndpoints, true)) {
            return $result;
        }

        return $this->authenticate($request);
    }

    /**
     * Generate authentication response.
     *
     * @since 1.0.0
     *
     * @param \WP_User $user User object.
     * @param string   $strategy Strategy used.
     * @return array
     */
    public function generateAuthResponse($user, $strategy = WP_API_CODEIA_AUTH_JWT)
    {
        $response = array(
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'roles' => $user->roles,
                'capabilities' => $this->getUserCapabilities($user),
            ),
            'strategy' => $strategy,
        );

        // Add tokens for JWT strategy
        if ($strategy === WP_API_CODEIA_AUTH_JWT) {
            $jwtStrategy = $this->getStrategy(WP_API_CODEIA_AUTH_JWT);
            if ($jwtStrategy) {
                $tokens = $jwtStrategy->generateTokens($user);
                $response['tokens'] = $tokens;
            }
        }

        $this->logger->info('Authentication successful', array(
            'user_id' => $user->ID,
            'strategy' => $strategy,
        ));

        return $response;
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
        $metaCaps = array_filter($allCaps, function ($cap) {
            return strpos($cap, 'level_') === 0;
        });

        return array_values(array_diff($allCaps, $metaCaps));
    }

    /**
     * Logout user.
     *
     * @since 1.0.0
     *
     * @param \WP_User $user User object.
     * @param string   $strategy Strategy used.
     * @return bool
     */
    public function logout($user, $strategy = WP_API_CODEIA_AUTH_JWT)
    {
        $strategyInstance = $this->getStrategy($strategy);

        if ($strategyInstance && method_exists($strategyInstance, 'revoke')) {
            $result = $strategyInstance->revoke($user);

            $this->logger->info('User logged out', array(
                'user_id' => $user->ID,
                'strategy' => $strategy,
            ));

            return $result;
        }

        return false;
    }

    /**
     * Validate token.
     *
     * @since 1.0.0
     *
     * @param string $token Token to validate.
     * @param string $strategy Strategy to use.
     * @return \WP_User|\WP_Error
     */
    public function validateToken($token, $strategy = WP_API_CODEIA_AUTH_JWT)
    {
        $strategyInstance = $this->getStrategy($strategy);

        if (!$strategyInstance) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid authentication strategy'
            );
        }

        $credentials = array(
            'type' => $strategy,
            'token' => $token,
        );

        return $strategyInstance->authenticate($credentials);
    }

    /**
     * Refresh token.
     *
     * @since 1.0.0
     *
     * @param string $refresh_token Refresh token.
     * @return array|\WP_Error
     */
    public function refreshToken($refresh_token)
    {
        $strategyInstance = $this->getStrategy(WP_API_CODEIA_AUTH_JWT);

        if (!$strategyInstance || !method_exists($strategyInstance, 'refresh')) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Token refresh not supported'
            );
        }

        return $strategyInstance->refresh($refresh_token);
    }

    /**
     * Check if endpoint is public.
     *
     * @since 1.0.0
     *
     * @param string $route Route to check.
     * @return bool
     */
    public function isPublicEndpoint($route)
    {
        $publicEndpoints = apply_filters('wp_api_codeia_public_endpoints', array(
            '/' . WP_API_CODEIA_API_NAMESPACE . '/v1/docs',
            '/' . WP_API_CODEIA_API_NAMESPACE . '/v1/docs/swagger',
            '/' . WP_API_CODEIA_API_NAMESPACE . '/v1/health',
        ));

        return in_array($route, $publicEndpoints, true);
    }
}
