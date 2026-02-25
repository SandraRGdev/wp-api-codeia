<?php
/**
 * Service Interface
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Core\Interfaces;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for all service classes.
 *
 * @since 1.0.0
 */
interface ServiceInterface
{
    /**
     * Register the service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register();

    /**
     * Bootstrap the service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot();
}
