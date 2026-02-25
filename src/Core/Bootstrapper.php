<?php
/**
 * Bootstrapper
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Core;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bootstrapper class that initializes the plugin.
 *
 * @since 1.0.0
 */
class Bootstrapper
{
    /**
     * Service container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Service provider instance.
     *
     * @var ServiceProvider
     */
    protected $provider;

    /**
     * Event dispatcher instance.
     *
     * @var EventDispatcher
     */
    protected $events;

    /**
     * Whether the plugin has been booted.
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * Create a new Bootstrapper instance.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->container = new Container();
        $this->provider = new ServiceProvider($this->container);
        $this->events = new EventDispatcher();
    }

    /**
     * Boot the plugin.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        $this->registerServices();
        $this->bootServices();
        $this->registerHooks();

        $this->booted = true;

        // Store instance globally.
        $GLOBALS['wp_api_codeia'] = $this;
    }

    /**
     * Register all services.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function registerServices()
    {
        // Register service provider first
        $this->container->singleton('provider', $this->provider);
        $this->container->singleton('events', $this->events);

        // Register services through provider
        $this->provider->register();

        // Register core services
        $this->container->singleton('config', function () {
            return new Config();
        });
    }

    /**
     * Boot all registered services.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function bootServices()
    {
        // Boot service provider (which boots all registered services)
        $this->provider->boot();

        // Dispatch core boot event
        $this->events->dispatch('core.boot');
    }

    /**
     * Register WordPress hooks.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function registerHooks()
    {
        // Register REST API initialization hook
        add_action('rest_api_init', array($this, 'registerRestRoutes'));

        // Register activation/deactivation hooks are in main plugin file
    }

    /**
     * Register REST routes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function registerRestRoutes()
    {
        // Routes will be registered in Sprint 2+ (API Layer)
        // For now, register a simple status endpoint
        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/' . WP_API_CODEIA_API_VERSION, '/status', array(
            'methods' => 'GET',
            'callback' => array($this, 'getStatus'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Get plugin status.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request The REST request.
     * @return \WP_REST_Response The response.
     */
    public function getStatus(\WP_REST_Request $request)
    {
        return rest_ensure_response(array(
            'success' => true,
            'data' => array(
                'plugin' => 'WP API Codeia',
                'version' => WP_API_CODEIA_VERSION,
                'status' => 'active',
                'namespace' => WP_API_CODEIA_API_NAMESPACE,
                'api_version' => WP_API_CODEIA_API_VERSION,
            ),
            'meta' => array(
                'timestamp' => current_time('mysql'),
                'version' => WP_API_CODEIA_API_VERSION,
            ),
        ));
    }

    /**
     * Get the service container.
     *
     * @since 1.0.0
     *
     * @return Container The service container.
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get the event dispatcher.
     *
     * @since 1.0.0
     *
     * @return EventDispatcher The event dispatcher.
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Check if the plugin has been booted.
     *
     * @since 1.0.0
     *
     * @return bool True if booted, false otherwise.
     */
    public function isBooted()
    {
        return $this->booted;
    }
}
