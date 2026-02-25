<?php
/**
 * Event Dispatcher
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Core;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

use WP_API_Codeia\Core\Interfaces\ServiceInterface;

/**
 * Event dispatcher for plugin events.
 *
 * @since 1.0.0
 */
class EventDispatcher implements ServiceInterface
{
    /**
     * Registered event listeners.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $listeners = array();

    /**
     * Wildcard listeners cache.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $wildcards = array();

    /**
     * Sorted wildcard listeners.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $wildcardsSorted = array();

    /**
     * Create a new EventDispatcher instance.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Hook into WordPress for integration
        $this->registerWordPressHooks();
    }

    /**
     * Register the event dispatcher service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        // Register internal events
        $this->registerInternalEvents();
    }

    /**
     * Boot the event dispatcher service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        // Nothing to boot
    }

    /**
     * Register an event listener.
     *
     * @since 1.0.0
     *
     * @param string   $event Event name.
     * @param callable $listener Callback function.
     * @param int      $priority Priority (higher = earlier execution).
     * @return void
     */
    public function listen($event, callable $listener, $priority = 10)
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = array();
        }

        $this->listeners[$event][] = array(
            'listener' => $listener,
            'priority' => $priority,
        );

        // Check if this is a wildcard
        if (strpos($event, '*') !== false) {
            $this->wildcards[] = $event;
            $this->wildcardsSorted = array(); // Clear cache
        }

        $this->sortListeners($event);
    }

    /**
     * Remove an event listener.
     *
     * @since 1.0.0
     *
     * @param string   $event Event name.
     * @param callable $listener Callback function.
     * @return void
     */
    public function forget($event, callable $listener)
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        $this->listeners[$event] = array_filter(
            $this->listeners[$event],
            function ($item) use ($listener) {
                return $item['listener'] !== $listener;
            }
        );
    }

    /**
     * Dispatch an event.
     *
     * @since 1.0.0
     *
     * @param string $event Event name.
     * @param mixed  ...$payload Event payload.
     * @return array Array of results from listeners.
     */
    public function dispatch($event, ...$payload)
    {
        $results = array();

        // Get direct listeners
        $listeners = $this->getListeners($event);

        // Get wildcard listeners
        $wildcards = $this->getWildcardListeners($event);

        // Combine and sort by priority
        $allListeners = array_merge($listeners, $wildcards);
        $this->sortListenersArray($allListeners);

        foreach ($allListeners as $listener) {
            $result = call_user_func_array($listener['listener'], array_merge(array($event), $payload));
            $results[] = $result;
        }

        return $results;
    }

    /**
     * Dispatch an event until a non-null result is returned.
     *
     * @since 1.0.0
     *
     * @param string $event Event name.
     * @param mixed  ...$payload Event payload.
     * @return mixed First non-null result.
     */
    public function until($event, ...$payload)
    {
        $listeners = $this->getListeners($event);
        $wildcards = $this->getWildcardListeners($event);

        $allListeners = array_merge($listeners, $wildcards);
        $this->sortListenersArray($allListeners);

        foreach ($allListeners as $listener) {
            $result = call_user_func_array($listener['listener'], array_merge(array($event), $payload));

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Check if an event has listeners.
     *
     * @since 1.0.0
     *
     * @param string $event Event name.
     * @return bool True if has listeners, false otherwise.
     */
    public function hasListeners($event)
    {
        return !empty($this->getListeners($event)) || !empty($this->getWildcardListeners($event));
    }

    /**
     * Get listeners for an event.
     *
     * @since 1.0.0
     *
     * @param string $event Event name.
     * @return array Array of listeners.
     */
    protected function getListeners($event)
    {
        return isset($this->listeners[$event]) ? $this->listeners[$event] : array();
    }

    /**
     * Get wildcard listeners for an event.
     *
     * @since 1.0.0
     *
     * @param string $event Event name.
     * @return array Array of wildcard listeners.
     */
    protected function getWildcardListeners($event)
    {
        $wildcards = array();

        // Sort wildcards by specificity (more specific first)
        if (empty($this->wildcardsSorted)) {
            $this->wildcardsSorted = $this->wildcards;
            usort($this->wildcardsSorted, function ($a, $b) {
                $aCount = substr_count($a, '*');
                $bCount = substr_count($b, '*');
                if ($aCount !== $bCount) {
                    return $aCount > $bCount ? 1 : -1;
                }
                return strcmp($a, $b);
            });
        }

        foreach ($this->wildcardsSorted as $wildcard) {
            if ($this->wildcardMatches($wildcard, $event)) {
                $wildcards = array_merge($wildcards, isset($this->listeners[$wildcard]) ? $this->listeners[$wildcard] : array());
            }
        }

        return $wildcards;
    }

    /**
     * Check if a wildcard pattern matches an event.
     *
     * @since 1.0.0
     *
     * @param string $wildcard Wildcard pattern.
     * @param string $event Event name.
     * @return bool True if matches, false otherwise.
     */
    protected function wildcardMatches($wildcard, $event)
    {
        $pattern = str_replace('*', '.*', preg_quote($wildcard, '/'));

        return (bool) preg_match('/^' . $pattern . '$/', $event);
    }

    /**
     * Sort listeners by priority.
     *
     * @since 1.0.0
     *
     * @param string $event Event name.
     * @return void
     */
    protected function sortListeners($event)
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        usort($this->listeners[$event], function ($a, $b) {
            if ($a['priority'] === $b['priority']) {
                return 0;
            }

            return $a['priority'] < $b['priority'] ? 1 : -1;
        });
    }

    /**
     * Sort listeners array.
     *
     * @since 1.0.0
     *
     * @param array $listeners Array of listeners.
     * @return void
     */
    protected function sortListenersArray(&$listeners)
    {
        usort($listeners, function ($a, $b) {
            if ($a['priority'] === $b['priority']) {
                return 0;
            }

            return $a['priority'] < $b['priority'] ? 1 : -1;
        });
    }

    /**
     * Register internal plugin events.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function registerInternalEvents()
    {
        // Core events
        $this->listen('core.boot', array($this, 'onCoreBoot'));
        $this->listen('auth.login', array($this, 'onAuthLogin'));
        $this->listen('auth.logout', array($this, 'onAuthLogout'));
        $this->listen('schema.changed', array($this, 'onSchemaChanged'));
        $this->listen('cache.cleared', array($this, 'onCacheCleared'));
    }

    /**
     * Register WordPress hooks for event integration.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function registerWordPressHooks()
    {
        // Integrate with WordPress hooks
        add_action('wp_api_codeia_boot', array($this, 'onCoreBoot'));
        add_action('wp_login', array($this, 'onWpLogin'));
        add_action('wp_logout', array($this, 'onWpLogout'));
    }

    /**
     * Core boot event handler.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function onCoreBoot()
    {
        do_action('wp_api_codeia_ready');
    }

    /**
     * Auth login event handler.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return void
     */
    public function onAuthLogin($user_id)
    {
        // Log successful API login
        do_action('wp_api_codeia_auth_success', $user_id);
    }

    /**
     * Auth logout event handler.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return void
     */
    public function onAuthLogout($user_id)
    {
        // Log API logout
        do_action('wp_api_codeia_auth_logout', $user_id);
    }

    /**
     * WordPress login event handler.
     *
     * @since 1.0.0
     *
     * @param string $user_login Username.
     * @param \WP_User $user User object.
     * @return void
     */
    public function onWpLogin($user_login, $user)
    {
        // Sync with internal auth events
        $this->dispatch('auth.login', $user->ID);
    }

    /**
     * WordPress logout event handler.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return void
     */
    public function onWpLogout($user_id)
    {
        // Sync with internal auth events
        $this->dispatch('auth.logout', $user_id);
    }

    /**
     * Schema changed event handler.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function onSchemaChanged()
    {
        // Clear cache when schema changes
        if (function_exists('wp_api_codeia_get_cache')) {
            $cache = wp_api_codeia_get_cache();
            if ($cache && $cache->isEnabled()) {
                $cache->clear('schema');
            }
        }
    }

    /**
     * Cache cleared event handler.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function onCacheCleared()
    {
        // Log cache clear event
        do_action('wp_api_codeia_cache_cleared');
    }

    /**
     * Get all registered listeners for an event.
     *
     * @since 1.0.0
     *
     * @param string $event Event name.
     * @return array Array of listener info.
     */
    public function getListenersInfo($event)
    {
        $info = array(
            'direct' => count($this->getListeners($event)),
            'wildcard' => count($this->getWildcardListeners($event)),
            'total' => 0,
        );

        $info['total'] = $info['direct'] + $info['wildcard'];

        return $info;
    }

    /**
     * Get all registered events.
     *
     * @since 1.0.0
     *
     * @return array Array of event names.
     */
    public function getEvents()
    {
        return array_keys($this->listeners);
    }
}
