<?php
/**
 * Authentication Service Provider
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Auth;

use WP_API_Codeia\Core\Interfaces\ServiceInterface;
use WP_API_Codeia\Core\Container;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Authentication Service Provider.
 *
 * Registers and boots all authentication-related services.
 *
 * @since 1.0.0
 */
class AuthProvider implements ServiceInterface
{
    /**
     * Container instance.
     *
     * @since 1.0.0
     *
     * @var Container
     */
    protected $container;

    /**
     * Create a new Auth Provider instance.
     *
     * @since 1.0.0
     *
     * @param Container $container DI Container.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register authentication services.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        // Token Manager
        $this->container->singleton('token_manager', function () {
            $logger = $this->container->get('logger');
            return new Token\TokenManager($logger);
        });

        // JWT Strategy
        $this->container->singleton('auth.jwt', function () {
            $tokenManager = $this->container->get('token_manager');
            $logger = $this->container->get('logger');
            return new Strategies\JWTStrategy($tokenManager, $logger);
        });

        // API Key Strategy
        $this->container->singleton('auth.api_key', function () {
            $logger = $this->container->get('logger');
            return new Strategies\APIKeyStrategy($logger);
        });

        // App Password Strategy
        $this->container->singleton('auth.app_password', function () {
            $logger = $this->container->get('logger');
            return new Strategies\AppPasswordStrategy($logger);
        });

        // Auth Manager
        $this->container->singleton('auth', function () {
            $manager = new Manager(
                $this->container,
                $this->container->get('logger')
            );

            // Register strategies
            $manager->registerStrategy(WP_API_CODEIA_AUTH_JWT, $this->container->get('auth.jwt'));
            $manager->registerStrategy(WP_API_CODEIA_AUTH_API_KEY, $this->container->get('auth.api_key'));
            $manager->registerStrategy(WP_API_CODEIA_AUTH_APP_PASSWORD, $this->container->get('auth.app_password'));

            return $manager;
        });

        // Auth Controller
        $this->container->singleton('auth.controller', function () {
            return new Controllers\AuthController(
                $this->container->get('auth'),
                $this->container->get('logger')
            );
        });
    }

    /**
     * Boot authentication services.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        // Register REST routes
        add_action('rest_api_init', array($this, 'registerRoutes'));

        // TEMPORARILY DISABLED - This filter is breaking core WP endpoints like /wp/v2/users/me
        // TODO: Fix the determineCurrentUser method to properly skip WP core endpoints
        // add_filter('determine_current_user', array($this, 'determineCurrentUser'), 20);

        // Schedule token cleanup
        add_action('wp_api_codeia_cleanup_tokens', array($this, 'cleanupTokens'));
        if (!wp_next_scheduled('wp_api_codeia_cleanup_tokens')) {
            wp_schedule_event(time(), 'daily', 'wp_api_codeia_cleanup_tokens');
        }
    }

    /**
     * Register REST API routes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function registerRoutes()
    {
        $controller = $this->container->get('auth.controller');
        $controller->register_routes();
    }

    /**
     * Determine current user from authentication.
     *
     * @since 1.0.0
     *
     * @param int|false $user_id User ID from previous filters.
     * @return int|false User ID or false.
     */
    public function determineCurrentUser($user_id)
    {
        // Already authenticated by WordPress or another plugin
        if ($user_id) {
            return $user_id;
        }

        // Early exit if not in REST API context
        if (!defined('REST_REQUEST') || !REST_REQUEST) {
            return false;
        }

        // Only authenticate our namespace - explicitly skip WordPress core endpoints
        $request = $this->getCurrentRequest();

        if ($request === null) {
            return false;
        }

        // Get route safely
        $route = $request->get_route();
        if (!is_string($route)) {
            return false;
        }

        // Skip WordPress core REST API endpoints (they start with /wp/v2/ or /oembed/)
        if (strpos($route, '/wp/v2/') === 0 || strpos($route, '/oembed/') === 0) {
            return false;
        }

        // Only authenticate our namespace
        $namespace = '/' . WP_API_CODEIA_API_NAMESPACE . '/';
        if (strpos($route, $namespace) !== 0) {
            return false;
        }

        // Skip public endpoints
        if ($this->isPublicEndpoint($route)) {
            return false;
        }

        // Authenticate
        try {
            if (!method_exists($this->container, 'get')) {
                return false;
            }
            $auth = $this->container->get('auth');
            $result = $auth->authenticate($request);

            if (is_wp_error($result)) {
                return false;
            }

            return $result->ID;
        } catch (\Exception $e) {
            // Return false on any error to avoid breaking core functionality
            return false;
        } catch (\Error $e) {
            // Catch fatal errors too
            return false;
        }
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
        // Only try to get request if REST server is available
        if (!did_action('rest_api_init')) {
            return null;
        }

        try {
            $server = rest_get_server();
            if ($server && method_exists($server, 'get_current_request')) {
                return $server->get_current_request();
            }
        } catch (\Exception $e) {
            return null;
        } catch (\Error $e) {
            return null;
        }

        return null;
    }

    /**
     * Check if endpoint is public.
     *
     * @since 1.0.0
     *
     * @param string $route Route to check.
     * @return bool
     */
    protected function isPublicEndpoint($route)
    {
        $publicEndpoints = apply_filters('wp_api_codeia_public_endpoints', array(
            '/' . WP_API_CODEIA_API_NAMESPACE . '/v1/docs',
            '/' . WP_API_CODEIA_API_NAMESPACE . '/v1/docs/swagger',
            '/' . WP_API_CODEIA_API_NAMESPACE . '/v1/status',
            '/' . WP_API_CODEIA_API_NAMESPACE . '/v1/auth/login',
            '/' . WP_API_CODEIA_API_NAMESPACE . '/v1/auth/refresh',
        ));

        return in_array($route, $publicEndpoints, true);
    }

    /**
     * Clean up expired tokens.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function cleanupTokens()
    {
        $tokenManager = $this->container->get('token_manager');
        $tokenManager->cleanupExpiredTokens();
    }
}
