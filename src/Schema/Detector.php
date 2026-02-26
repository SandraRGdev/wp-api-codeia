<?php
/**
 * Schema Detector
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Schema;

use WP_API_Codeia\Core\Interfaces\ServiceInterface;
use WP_API_Codeia\Core\Container;
use WP_API_Codeia\Utils\Cache\CacheManager;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schema Detector.
 *
 * Main detector that coordinates schema detection for post types,
 * fields, taxonomies, and integrations with third-party plugins.
 *
 * @since 1.0.0
 */
class Detector implements ServiceInterface
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
     * Detected schema cache.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $schemaCache = array();

    /**
     * Post Type Detector instance.
     *
     * @since 1.0.0
     *
     * @var PostTypeDetector
     */
    protected $postTypeDetector;

    /**
     * Field Detector instance.
     *
     * @since 1.0.0
     *
     * @var FieldDetector
     */
    protected $fieldDetector;

    /**
     * Taxonomy Detector instance.
     *
     * @since 1.0.0
     *
     * @var TaxonomyDetector
     */
    protected $taxonomyDetector;

    /**
     * Create a new Schema Detector instance.
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

        // Initialize sub-detectors immediately to prevent null pointer errors
        $this->postTypeDetector = new PostTypeDetector($cache, $logger);
        $this->fieldDetector = new FieldDetector($cache, $logger);
        $this->taxonomyDetector = new TaxonomyDetector($cache, $logger);
    }

    /**
     * Register the schema detector service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        // Sub-detectors are now initialized in constructor
        // This method is kept for interface compatibility
    }

    /**
     * Boot the schema detector service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        // Clear cache on post type changes
        add_action('registered_post_type', array($this, 'clearSchemaCache'));
        add_action('unregistered_post_type', array($this, 'clearSchemaCache'));

        // Clear cache on field changes
        add_action('acf/add_fields', array($this, 'clearSchemaCache'));
        add_action('acf/delete_field', array($this, 'clearSchemaCache'));
    }

    /**
     * Detect complete schema.
     *
     * @since 1.0.0
     *
     * @param bool $refresh Force refresh cache.
     * @return array Complete schema.
     */
    public function detect($refresh = false)
    {
        $cacheKey = 'codeia_full_schema';

        if (!$refresh) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $schema = array(
            'post_types' => $this->detectPostTypes($refresh),
            'taxonomies' => $this->detectTaxonomies($refresh),
            'meta_boxes' => $this->detectMetaBoxes($refresh),
            'integrations' => $this->detectIntegrations($refresh),
        );

        $this->cache->set($cacheKey, $schema, 3600);

        $this->logger->debug('Schema detected', array(
            'post_types' => count($schema['post_types']),
            'taxonomies' => count($schema['taxonomies']),
        ));

        return $schema;
    }

    /**
     * Detect post types.
     *
     * @since 1.0.0
     *
     * @param bool $refresh Force refresh cache.
     * @return array
     */
    public function detectPostTypes($refresh = false)
    {
        return $this->postTypeDetector->detect($refresh);
    }

    /**
     * Detect fields for a post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @param bool   $refresh  Force refresh cache.
     * @return array
     */
    public function detectFields($postType, $refresh = false)
    {
        return $this->fieldDetector->detectForPostType($postType, $refresh);
    }

    /**
     * Detect taxonomies.
     *
     * @since 1.0.0
     *
     * @param bool $refresh Force refresh cache.
     * @return array
     */
    public function detectTaxonomies($refresh = false)
    {
        return $this->taxonomyDetector->detect($refresh);
    }

    /**
     * Detect meta boxes.
     *
     * @since 1.0.0
     *
     * @param bool $refresh Force refresh cache.
     * @return array
     */
    public function detectMetaBoxes($refresh = false)
    {
        global $wp_meta_boxes;

        $cacheKey = 'codeia_meta_boxes';

        if (!$refresh) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $metaBoxes = array();

        if (empty($wp_meta_boxes)) {
            return $metaBoxes;
        }

        foreach ($wp_meta_boxes as $postType => $contexts) {
            if (!isset($metaBoxes[$postType])) {
                $metaBoxes[$postType] = array();
            }

            foreach ($contexts as $context => $priorities) {
                foreach ($priorities as $priority => $boxes) {
                    foreach ($boxes as $boxId => $box) {
                        $metaBoxes[$postType][] = array(
                            'id' => $boxId,
                            'title' => $box['title'],
                            'context' => $context,
                            'priority' => $priority,
                        );
                    }
                }
            }
        }

        $this->cache->set($cacheKey, $metaBoxes, 3600);

        return $metaBoxes;
    }

    /**
     * Detect third-party integrations.
     *
     * @since 1.0.0
     *
     * @param bool $refresh Force refresh cache.
     * @return array
     */
    public function detectIntegrations($refresh = false)
    {
        $integrations = array(
            'acf' => $this->detectACF(),
            'jet_engine' => $this->detectJetEngine(),
            'metabox' => $this->detectMetaBox(),
            'carbon_fields' => $this->detectCarbonFields(),
            'pods' => $this->detectPods(),
            'toolset' => $this->detectToolset(),
        );

        return array_filter($integrations);
    }

    /**
     * Detect ACF (Advanced Custom Fields).
     *
     * @since 1.0.0
     *
     * @return array|null
     */
    protected function detectACF()
    {
        if (!class_exists('ACF')) {
            return null;
        }

        $fieldGroups = acf_get_field_groups();

        $groups = array();

        foreach ($fieldGroups as $group) {
            $groups[] = array(
                'key' => $group['key'],
                'title' => $group['title'],
                'fields' => $this->getACFFields($group['key']),
                'location' => $group['location'],
            );
        }

        return array(
            'version' => defined('ACF_VERSION') ? ACF_VERSION : 'unknown',
            'active' => true,
            'field_groups' => $groups,
        );
    }

    /**
     * Get ACF fields for a field group.
     *
     * @since 1.0.0
     *
     * @param string $groupKey Field group key.
     * @return array
     */
    protected function getACFFields($groupKey)
    {
        $fields = acf_get_fields($groupKey);

        $mapped = array();

        foreach ($fields as $field) {
            $mapped[] = array(
                'key' => $field['key'],
                'label' => $field['label'],
                'name' => $field['name'],
                'type' => $field['type'],
                'required' => isset($field['required']) ? (bool) $field['required'] : false,
            );
        }

        return $mapped;
    }

    /**
     * Detect JetEngine.
     *
     * @since 1.0.0
     *
     * @return array|null
     */
    protected function detectJetEngine()
    {
        if (!class_exists('Jet_Engine')) {
            return null;
        }

        $listings = array();

        if (class_exists('Jet_Engine\Listings')) {
            $listingsManager = \Jet_Engine\Listings::instance();
            // Get listings if available
        }

        return array(
            'version' => defined('JET_ENGINE_VERSION') ? JET_ENGINE_VERSION : 'unknown',
            'active' => true,
            'listings' => $listings,
        );
    }

    /**
     * Detect Meta Box.
     *
     * @since 1.0.0
     *
     * @return array|null
     */
    protected function detectMetaBox()
    {
        if (!defined('RWMB_VER')) {
            return null;
        }

        $metaBoxes = rwmb_get_registry('meta_box')->all();

        $boxes = array();

        foreach ($metaBoxes as $metaBox) {
            $boxes[] = array(
                'id' => $metaBox->id,
                'title' => $metaBox->title,
                'fields' => $this->getMetaBoxFields($metaBox),
            );
        }

        return array(
            'version' => defined('RWMB_VER') ? RWMB_VER : 'unknown',
            'active' => true,
            'meta_boxes' => $boxes,
        );
    }

    /**
     * Get Meta Box fields.
     *
     * @since 1.0.0
     *
     * @param object $metaBox Meta Box instance.
     * @return array
     */
    protected function getMetaBoxFields($metaBox)
    {
        $fields = array();

        foreach ($metaBox->fields as $field) {
            $fields[] = array(
                'id' => $field['id'],
                'name' => $field['name'],
                'type' => $field['type'],
            );
        }

        return $fields;
    }

    /**
     * Detect Carbon Fields.
     *
     * @since 1.0.0
     *
     * @return array|null
     */
    protected function detectCarbonFields()
    {
        if (!class_exists('Carbon_Fields\Carbon_Fields')) {
            return null;
        }

        return array(
            'version' => defined('CARBON_FIELDS_VERSION') ? CARBON_FIELDS_VERSION : 'unknown',
            'active' => true,
        );
    }

    /**
     * Detect Pods.
     *
     * @since 1.0.0
     *
     * @return array|null
     */
    protected function detectPods()
    {
        if (!function_exists('pods')) {
            return null;
        }

        $allPods = pods_api()->load_pods(array());

        return array(
            'version' => defined('PODS_VERSION') ? PODS_VERSION : 'unknown',
            'active' => true,
            'pods' => $allPods,
        );
    }

    /**
     * Detect Toolset.
     *
     * @since 1.0.0
     *
     * @return array|null
     */
    protected function detectToolset()
    {
        if (!defined('TYPES_VERSION')) {
            return null;
        }

        return array(
            'version' => defined('TYPES_VERSION') ? TYPES_VERSION : 'unknown',
            'active' => true,
        );
    }

    /**
     * Get schema for a specific post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array
     */
    public function getPostTypeSchema($postType)
    {
        return array(
            'post_type' => $postType,
            'info' => $this->postTypeDetector->getInfo($postType),
            'fields' => $this->detectFields($postType),
            'taxonomies' => $this->taxonomyDetector->getForPostType($postType),
            'meta_boxes' => $this->detectMetaBoxesForPostType($postType),
        );
    }

    /**
     * Detect meta boxes for a specific post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array
     */
    protected function detectMetaBoxesForPostType($postType)
    {
        $allMetaBoxes = $this->detectMetaBoxes();

        return isset($allMetaBoxes[$postType]) ? $allMetaBoxes[$postType] : array();
    }

    /**
     * Clear schema cache.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function clearSchemaCache()
    {
        $this->cache->delete('codeia_full_schema');
        $this->cache->delete('codeia_meta_boxes');
        $this->cache->delete('codeia_post_types');
        $this->cache->delete('codeia_taxonomies');

        do_action('wp_api_codeia_schema_cache_cleared');
    }

    /**
     * Get post type detector.
     *
     * @since 1.0.0
     *
     * @return PostTypeDetector
     */
    public function getPostTypeDetector()
    {
        return $this->postTypeDetector;
    }

    /**
     * Get field detector.
     *
     * @since 1.0.0
     *
     * @return FieldDetector
     */
    public function getFieldDetector()
    {
        return $this->fieldDetector;
    }

    /**
     * Get taxonomy detector.
     *
     * @since 1.0.0
     *
     * @return TaxonomyDetector
     */
    public function getTaxonomyDetector()
    {
        return $this->taxonomyDetector;
    }
}
