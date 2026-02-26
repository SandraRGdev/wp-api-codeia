<?php
/**
 * Service Provider
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Core;

use WP_API_Codeia\Auth\AuthProvider;
use WP_API_Codeia\Admin\Page;
use WP_API_Codeia\API\Router;
use WP_API_Codeia\API\ResponseFormatter;
use WP_API_Codeia\Documentation\Generator;
use WP_API_Codeia\Documentation\SwaggerUIRenderer;
use WP_API_Codeia\Utils\Logger\Logger;
use WP_API_Codeia\Utils\Cache\CacheManager;
use WP_API_Codeia\Permissions\Manager;
use WP_API_Codeia\Permissions\RateLimiter;
use WP_API_Codeia\Permissions\Middleware;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Service provider registry.
 *
 * @since 1.0.0
 */
class ServiceProvider implements Interfaces\ServiceInterface
{
    /**
     * The DI container instance.
     *
     * @since 1.0.0
     *
     * @var Container
     */
    protected $container;

    /**
     * Registered service providers.
     *
     * @var array
     */
    protected $providers = array();

    /**
     * Booted service providers.
     *
     * @var array
     */
    protected $booted = array();

    /**
     * Create a new ServiceProvider instance.
     *
     * @since 1.0.0
     *
     * @param Container $container The DI container.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register all service providers.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        // Register util providers FIRST (logger, cache) - other services depend on them
        $this->registerUtilProviders();
        $this->registerCoreProviders();

        /**
         * Fires after all service providers are registered.
         *
         * @since 1.0.0
         *
         * @param ServiceProvider $provider The service provider instance.
         */
        do_action('wp_api_codeia_providers_registered', $this);
    }

    /**
     * Boot all registered service providers.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        foreach ($this->providers as $name => $provider) {
            if (!in_array($name, $this->booted, true)) {
                $this->bootProvider($name, $provider);
            }
        }

        /**
         * Fires after all service providers are booted.
         *
         * @since 1.0.0
         *
         * @param ServiceProvider $provider The service provider instance.
         */
        do_action('wp_api_codeia_providers_booted', $this);
    }

    /**
     * Register a service provider.
     *
     * @since 1.0.0
     *
     * @param string $name Provider name.
     * @param string|object $provider Provider class name or instance.
     * @return void
     */
    public function addProvider($name, $provider)
    {
        if (is_string($provider)) {
            $provider = new $provider($this->container);
        }

        $this->providers[$name] = $provider;

        if (method_exists($provider, 'register')) {
            $provider->register();
        }
    }

    /**
     * Boot a specific service provider.
     *
     * @since 1.0.0
     *
     * @param string $name Provider name.
     * @return void
     */
    protected function bootProvider($name, Interfaces\ServiceInterface $provider)
    {
        if (method_exists($provider, 'boot')) {
            $provider->boot();
        }

        $this->booted[] = $name;
    }

    /**
     * Get a registered service provider.
     *
     * @since 1.0.0
     *
     * @param string $name Provider name.
     * @return object|null The provider instance or null.
     */
    public function getProvider($name)
    {
        return isset($this->providers[$name]) ? $this->providers[$name] : null;
    }

    /**
     * Register core service providers.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function registerCoreProviders()
    {
        $this->addProvider('config', Config::class);
        $this->addProvider('auth', AuthProvider::class);
        $this->addProvider('admin', Page::class);

        // API Router and related services
        $this->container->singleton('response_formatter', function () {
            return new ResponseFormatter();
        });

        $this->container->singleton('detector', function () {
            $detector = new \WP_API_Codeia\Schema\Detector(
                $this->container->get('cache'),
                $this->container->get('logger')
            );
            $detector->register(); // Important: initialize sub-detectors
            return $detector;
        });

        // Permissions services (must be registered before router)
        $this->container->singleton('permissions_manager', function () {
            return new Manager(
                $this->container->get('cache'),
                $this->container->get('logger')
            );
        });

        $this->container->singleton('rate_limiter', function () {
            return new RateLimiter(
                $this->container->get('cache'),
                $this->container->get('logger')
            );
        });

        $this->container->singleton('middleware', function () {
            return new Middleware(
                $this->container->get('permissions_manager'),
                $this->container->get('rate_limiter')
            );
        });

        // Router (registered after middleware since it depends on it)
        $this->container->singleton('router', function () {
            return new Router(
                $this->container,
                $this->container->get('detector'),
                $this->container->get('logger'),
                $this->container->get('middleware')
            );
        });

        // Documentation services
        $this->container->singleton('docs_generator', function () {
            return new Generator(
                $this->container->get('detector'),
                $this->container->get('cache'),
                $this->container->get('logger')
            );
        });

        $this->container->singleton('docs_renderer', function () {
            return new SwaggerUIRenderer(
                $this->container->get('docs_generator'),
                $this->container->get('logger')
            );
        });

        // Initialize router and docs immediately after registration
        // This ensures hooks are registered before rest_api_init fires
        add_action('rest_api_init', function() {
            $router = $this->container->get('router');
            if (method_exists($router, 'registerRoutes')) {
                $router->registerRoutes();
            }
        }, 5); // Priority 5 to register before other routes

        add_action('rest_api_init', function() {
            $renderer = $this->container->get('docs_renderer');
            if (method_exists($renderer, 'registerRoutes')) {
                $renderer->registerRoutes();
            }
        }, 5); // Priority 5 to register before other routes

        // Boot the detector to register hooks
        add_action('plugins_loaded', function() {
            $detector = $this->container->get('detector');
            if (method_exists($detector, 'boot')) {
                $detector->boot();
            }
        }, 15); // After plugin initialization but before most hooks
    }

    /**
     * Register utility service providers.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function registerUtilProviders()
    {
        // Register logger in container FIRST - other services depend on it
        $this->container->singleton('logger', function () {
            return new Logger($this->container);
        });

        // Register cache in container FIRST - other services depend on it
        $this->container->singleton('cache', function () {
            return new CacheManager($this->container);
        });

        // Also register as providers for boot() calls
        $this->addProvider('logger', Logger::class);
        $this->addProvider('cache', CacheManager::class);
    }
}
