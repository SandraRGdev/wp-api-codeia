<?php
/**
 * Service Provider
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Core;

use WP_API_Codeia\Auth\AuthProvider;
use WP_API_Codeia\Admin\Page;
use WP_API_Codeia\Utils\Logger\Logger;
use WP_API_Codeia\Utils\Cache\CacheManager;

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
        $this->registerCoreProviders();
        $this->registerUtilProviders();

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
        // API provider will be added in Sprint 4
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
        $this->addProvider('logger', Logger::class);
        $this->addProvider('cache', CacheManager::class);
    }
}
