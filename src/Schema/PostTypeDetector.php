<?php
/**
 * Post Type Detector
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Schema;

use WP_API_Codeia\Utils\Cache\CacheManager;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Type Detector.
 *
 * Detects and caches WordPress post types information.
 *
 * @since 1.0.0
 */
class PostTypeDetector
{
    /**
     * Cache Manager instance.
     *
     * @since 1.0.0
     *
     * @var CacheManager
     */
    protected $cache;

    /**
     * Logger instance.
     *
     * @since 1.0.0
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Internal post types (excluded from API).
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $internalPostTypes = array(
        'attachment',
        'revision',
        'nav_menu_item',
        'custom_css',
        'customize_changeset',
        'oembed_cache',
        'user_request',
        'wp_block',
        'wp_template',
        'wp_template_part',
        'wp_global_styles',
        'navigation',
        'wp_navigation',
    );

    /**
     * Create a new Post Type Detector instance.
     *
     * @since 1.0.0
     *
     * @param CacheManager $cache Cache Manager.
     * @param Logger       $logger Logger instance.
     */
    public function __construct(CacheManager $cache, Logger $logger)
    {
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Detect all post types.
     *
     * @since 1.0.0
     *
     * @param bool $refresh Force refresh cache.
     * @return array
     */
    public function detect($refresh = false)
    {
        $cacheKey = 'codeia_post_types';

        if (!$refresh) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $postTypes = get_post_types(array('public' => true), 'objects');

        $detected = array();

        foreach ($postTypes as $postType) {
            if ($this->isInternal($postType->name)) {
                continue;
            }

            $detected[$postType->name] = $this->extractInfo($postType);
        }

        /**
         * Filter detected post types.
         *
         * @since 1.0.0
         *
         * @param array $detected Detected post types.
         */
        $detected = apply_filters('wp_api_codeia_detected_post_types', $detected);

        $this->cache->set($cacheKey, $detected, 3600);

        return $detected;
    }

    /**
     * Get info for a specific post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array|null
     */
    public function getInfo($postType)
    {
        $postTypeObject = get_post_type_object($postType);

        if (!$postTypeObject) {
            return null;
        }

        return $this->extractInfo($postTypeObject);
    }

    /**
     * Extract information from post type object.
     *
     * @since 1.0.0
     *
     * @param \WP_Post_Type $postType Post type object.
     * @return array
     */
    protected function extractInfo($postType)
    {
        $supports = get_all_post_type_supports($postType->name);

        return array(
            'name' => $postType->name,
            'label' => $postType->label,
            'labels' => array(
                'name' => $postType->labels->name,
                'singular_name' => $postType->labels->singular_name,
                'menu_name' => $postType->labels->menu_name,
            ),
            'description' => $postType->description,
            'public' => $postType->public,
            'hierarchical' => $postType->hierarchical,
            'show_ui' => $postType->show_ui,
            'show_in_rest' => $postType->show_in_rest,
            'rest_base' => $postType->rest_base,
            'has_archive' => $postType->has_archive,
            'supports' => array_keys($supports),
            'rewrite' => $postType->rewrite,
            'capability_type' => $postType->capability_type,
            'taxonomies' => get_object_taxonomies($postType->name),
            'api_visible' => $this->isVisibleInAPI($postType),
        );
    }

    /**
     * Check if post type should be visible in API.
     *
     * @since 1.0.0
     *
     * @param \WP_Post_Type $postType Post type object.
     * @return bool
     */
    protected function isVisibleInAPI($postType)
    {
        $visible = $postType->public && $postType->show_in_rest;

        return apply_filters('wp_api_codeia_post_type_visible', $visible, $postType->name);
    }

    /**
     * Check if post type is internal.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type name.
     * @return bool
     */
    protected function isInternal($postType)
    {
        $internal = apply_filters('wp_api_codeia_internal_post_types', $this->internalPostTypes);

        return in_array($postType, $internal, true);
    }

    /**
     * Get post type by REST base.
     *
     * @since 1.0.0
     *
     * @param string $restBase REST base.
     * @return string|null
     */
    public function getPostTypeByRestBase($restBase)
    {
        $postTypes = $this->detect();

        foreach ($postTypes as $name => $info) {
            if (isset($info['rest_base']) && $info['rest_base'] === $restBase) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Get supported post types for API.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getSupportedPostTypes()
    {
        $postTypes = $this->detect();

        return array_filter($postTypes, function ($info) {
            return isset($info['api_visible']) && $info['api_visible'];
        });
    }

    /**
     * Check if a post type is supported.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type name.
     * @return bool
     */
    public function isSupported($postType)
    {
        $supported = $this->getSupportedPostTypes();

        return isset($supported[$postType]);
    }

    /**
     * Get post type capabilities.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type name.
     * @return array
     */
    public function getCapabilities($postType)
    {
        $postTypeObject = get_post_type_object($postType);

        if (!$postTypeObject) {
            return array();
        }

        return array(
            'edit_post' => $postTypeObject->cap->edit_post,
            'read_post' => $postTypeObject->cap->read_post,
            'delete_post' => $postTypeObject->cap->delete_post,
            'edit_posts' => $postTypeObject->cap->edit_posts,
            'edit_others_posts' => $postTypeObject->cap->edit_others_posts,
            'publish_posts' => $postTypeObject->cap->publish_posts,
            'read_private_posts' => $postTypeObject->cap->read_private_posts,
        );
    }
}
