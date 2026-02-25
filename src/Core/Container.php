<?php
/**
 * Dependency Injection Container
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Core;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple dependency injection container.
 *
 * @since 1.0.0
 */
class Container
{
    /**
     * Registered services.
     *
     * @var array
     */
    protected $services = array();

    /**
     * Singleton instances.
     *
     * @var array
     */
    protected $instances = array();

    /**
     * Register a service.
     *
     * @since 1.0.0
     *
     * @param string $name Service name.
     * @param mixed  $resolver Service resolver (closure or concrete value).
     * @param bool   $singleton Whether the service should be a singleton.
     * @return void
     */
    public function bind($name, $resolver, $singleton = false)
    {
        $this->services[$name] = array(
            'resolver' => $resolver,
            'singleton' => $singleton,
        );
    }

    /**
     * Register a singleton service.
     *
     * @since 1.0.0
     *
     * @param string $name Service name.
     * @param mixed  $resolver Service resolver.
     * @return void
     */
    public function singleton($name, $resolver)
    {
        $this->bind($name, $resolver, true);
    }

    /**
     * Resolve a service.
     *
     * @since 1.0.0
     *
     * @param string $name Service name.
     * @return mixed The resolved service.
     * @throws \Exception If service is not found.
     */
    public function make($name)
    {
        // Return singleton instance if exists
        if (isset($this->instances[$name])) {
            return $this->instances[$name];
        }

        // Check if service is registered
        if (!isset($this->services[$name])) {
            throw new \Exception(sprintf('Service "%s" not found in container.', $name));
        }

        $service = $this->services[$name];

        // Resolve service
        $resolver = $service['resolver'];

        if ($resolver instanceof \Closure) {
            $instance = $resolver($this);
        } else {
            $instance = $resolver;
        }

        // Store if singleton
        if ($service['singleton']) {
            $this->instances[$name] = $instance;
        }

        return $instance;
    }

    /**
     * Check if a service is registered.
     *
     * @since 1.0.0
     *
     * @param string $name Service name.
     * @return bool True if registered, false otherwise.
     */
    public function has($name)
    {
        return isset($this->services[$name]) || isset($this->instances[$name]);
    }

    /**
     * Get a singleton instance or create if not exists.
     *
     * @since 1.0.0
     *
     * @param string $name Service name.
     * @param mixed  $resolver Service resolver (if not exists).
     * @return mixed The service instance.
     */
    public function get($name, $resolver = null)
    {
        if (!$this->has($name) && $resolver !== null) {
            $this->singleton($name, $resolver);
        }

        return $this->make($name);
    }
}
