<?php
/**
 * Taxonomy Detector
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
 * Taxonomy Detector.
 *
 * Detects and caches WordPress taxonomies information.
 *
 * @since 1.0.0
 */
class TaxonomyDetector
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
     * Internal taxonomies (excluded from API).
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $internalTaxonomies = array(
        'nav_menu',
        'link_category',
        'post_format',
        'wp_theme',
        'wp_template_part_area',
    );

    /**
     * Create a new Taxonomy Detector instance.
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
     * Detect all taxonomies.
     *
     * @since 1.0.0
     *
     * @param bool $refresh Force refresh cache.
     * @return array
     */
    public function detect($refresh = false)
    {
        $cacheKey = 'codeia_taxonomies';

        if (!$refresh) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $taxonomies = get_taxonomies(array('public' => true), 'objects');

        $detected = array();

        foreach ($taxonomies as $taxonomy) {
            if ($this->isInternal($taxonomy->name)) {
                continue;
            }

            $detected[$taxonomy->name] = $this->extractInfo($taxonomy);
        }

        /**
         * Filter detected taxonomies.
         *
         * @since 1.0.0
         *
         * @param array $detected Detected taxonomies.
         */
        $detected = apply_filters('wp_api_codeia_detected_taxonomies', $detected);

        $this->cache->set($cacheKey, $detected, 3600);

        return $detected;
    }

    /**
     * Get info for a specific taxonomy.
     *
     * @since 1.0.0
     *
     * @param string $taxonomy Taxonomy slug.
     * @return array|null
     */
    public function getInfo($taxonomy)
    {
        $taxonomyObject = get_taxonomy($taxonomy);

        if (!$taxonomyObject) {
            return null;
        }

        return $this->extractInfo($taxonomyObject);
    }

    /**
     * Extract information from taxonomy object.
     *
     * @since 1.0.0
     *
     * @param \WP_Taxonomy $taxonomy Taxonomy object.
     * @return array
     */
    protected function extractInfo($taxonomy)
    {
        return array(
            'name' => $taxonomy->name,
            'label' => $taxonomy->label,
            'labels' => array(
                'name' => $taxonomy->labels->name,
                'singular_name' => $taxonomy->labels->singular_name,
                'menu_name' => $taxonomy->labels->menu_name,
                'search_items' => $taxonomy->labels->search_items,
                'popular_items' => $taxonomy->labels->popular_items,
                'all_items' => $taxonomy->labels->all_items,
            ),
            'description' => $taxonomy->description,
            'public' => $taxonomy->public,
            'hierarchical' => $taxonomy->hierarchical,
            'show_ui' => $taxonomy->show_ui,
            'show_in_rest' => $taxonomy->show_in_rest,
            'rest_base' => $taxonomy->rest_base,
            'rest_namespace' => $taxonomy->rest_namespace,
            'show_in_menu' => $taxonomy->show_in_menu,
            'show_in_nav_menus' => $taxonomy->show_in_nav_menus,
            'show_tagcloud' => $taxonomy->show_tagcloud,
            'show_in_quick_edit' => $taxonomy->show_in_quick_edit,
            'show_admin_column' => $taxonomy->show_admin_column,
            'meta_box_cb' => $taxonomy->meta_box_cb,
            'object_type' => $taxonomy->object_type,
            'capabilities' => array(
                'manage_terms' => $taxonomy->cap->manage_terms,
                'edit_terms' => $taxonomy->cap->edit_terms,
                'delete_terms' => $taxonomy->cap->delete_terms,
                'assign_terms' => $taxonomy->cap->assign_terms,
            ),
            'rewrite' => $taxonomy->rewrite,
            'query_var' => $taxonomy->query_var,
            'update_count_callback' => $taxonomy->update_count_callback,
            'api_visible' => $this->isVisibleInAPI($taxonomy),
        );
    }

    /**
     * Check if taxonomy should be visible in API.
     *
     * @since 1.0.0
     *
     * @param \WP_Taxonomy $taxonomy Taxonomy object.
     * @return bool
     */
    protected function isVisibleInAPI($taxonomy)
    {
        $visible = $taxonomy->public && $taxonomy->show_in_rest;

        return apply_filters('wp_api_codeia_taxonomy_visible', $visible, $taxonomy->name);
    }

    /**
     * Check if taxonomy is internal.
     *
     * @since 1.0.0
     *
     * @param string $taxonomy Taxonomy name.
     * @return bool
     */
    protected function isInternal($taxonomy)
    {
        $internal = apply_filters('wp_api_codeia_internal_taxonomies', $this->internalTaxonomies);

        return in_array($taxonomy, $internal, true);
    }

    /**
     * Get taxonomies for a specific post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array
     */
    public function getForPostType($postType)
    {
        $allTaxonomies = $this->detect();

        $forPostType = array();

        foreach ($allTaxonomies as $name => $info) {
            if (in_array($postType, $info['object_type'], true)) {
                $forPostType[$name] = $info;
            }
        }

        return $forPostType;
    }

    /**
     * Get taxonomy by REST base.
     *
     * @since 1.0.0
     *
     * @param string $restBase REST base.
     * @return string|null
     */
    public function getTaxonomyByRestBase($restBase)
    {
        $taxonomies = $this->detect();

        foreach ($taxonomies as $name => $info) {
            if (isset($info['rest_base']) && $info['rest_base'] === $restBase) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Get supported taxonomies for API.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getSupportedTaxonomies()
    {
        $taxonomies = $this->detect();

        return array_filter($taxonomies, function ($info) {
            return isset($info['api_visible']) && $info['api_visible'];
        });
    }

    /**
     * Check if a taxonomy is supported.
     *
     * @since 1.0.0
     *
     * @param string $taxonomy Taxonomy name.
     * @return bool
     */
    public function isSupported($taxonomy)
    {
        $supported = $this->getSupportedTaxonomies();

        return isset($supported[$taxonomy]);
    }

    /**
     * Get terms for a taxonomy.
     *
     * @since 1.0.0
     *
     * @param string $taxonomy Taxonomy name.
     * @param array  $args     Query arguments.
     * @return array
     */
    public function getTerms($taxonomy, $args = array())
    {
        $defaults = array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => 0,
            'offset' => 0,
        );

        $args = wp_parse_args($args, $defaults);

        $terms = get_terms($args);

        if (is_wp_error($terms)) {
            return array();
        }

        return array_map(array($this, 'formatTerm'), $terms);
    }

    /**
     * Format term for API response.
     *
     * @since 1.0.0
     *
     * @param \WP_Term $term Term object.
     * @return array
     */
    public function formatTerm($term)
    {
        return array(
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'taxonomy' => $term->taxonomy,
            'description' => $term->description,
            'parent' => $term->parent,
            'count' => $term->count,
            'link' => get_term_link($term),
        );
    }

    /**
     * Get taxonomy capabilities.
     *
     * @since 1.0.0
     *
     * @param string $taxonomy Taxonomy name.
     * @return array
     */
    public function getCapabilities($taxonomy)
    {
        $taxonomyObject = get_taxonomy($taxonomy);

        if (!$taxonomyObject) {
            return array();
        }

        return array(
            'manage_terms' => $taxonomyObject->cap->manage_terms,
            'edit_terms' => $taxonomyObject->cap->edit_terms,
            'delete_terms' => $taxonomyObject->cap->delete_terms,
            'assign_terms' => $taxonomyObject->cap->assign_terms,
        );
    }
}
