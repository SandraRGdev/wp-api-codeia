<?php
/**
 * Field Detector
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
 * Field Detector.
 *
 * Detects and caches WordPress post fields including native fields,
 * meta fields, and integrations with third-party plugins.
 *
 * @since 1.0.0
 */
class FieldDetector
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
     * Native WordPress fields.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $nativeFields = array(
        'ID',
        'post_author',
        'post_date',
        'post_date_gmt',
        'post_content',
        'post_title',
        'post_excerpt',
        'post_status',
        'comment_status',
        'ping_status',
        'post_password',
        'post_name',
        'to_ping',
        'pinged',
        'post_modified',
        'post_modified_gmt',
        'post_content_filtered',
        'post_parent',
        'guid',
        'menu_order',
        'post_type',
        'post_mime_type',
        'comment_count',
        'filter',
    );

    /**
     * Create a new Field Detector instance.
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
     * Detect fields for a post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @param bool   $refresh  Force refresh cache.
     * @return array
     */
    public function detectForPostType($postType, $refresh = false)
    {
        $cacheKey = 'codeia_fields_' . $postType;

        if (!$refresh) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $fields = array(
            'native' => $this->getNativeFields(),
            'meta' => $this->getMetaFields($postType),
            'taxonomy' => $this->getTaxonomyFields($postType),
            'acf' => $this->getACFFields($postType),
            'jet_engine' => $this->getJetEngineFields($postType),
            'metabox' => $this->getMetaBoxFields($postType),
        );

        $this->cache->set($cacheKey, $fields, 3600);

        return $fields;
    }

    /**
     * Get native WordPress fields.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getNativeFields()
    {
        return array(
            'id' => array(
                'name' => 'id',
                'type' => 'integer',
                'description' => 'Unique identifier',
                'readonly' => true,
            ),
            'title' => array(
                'name' => 'title',
                'type' => 'string',
                'description' => 'Post title',
                'readonly' => false,
            ),
            'content' => array(
                'name' => 'content',
                'type' => 'string',
                'description' => 'Post content',
                'readonly' => false,
            ),
            'excerpt' => array(
                'name' => 'excerpt',
                'type' => 'string',
                'description' => 'Post excerpt',
                'readonly' => false,
            ),
            'status' => array(
                'name' => 'status',
                'type' => 'string',
                'description' => 'Post status',
                'readonly' => false,
                'enum' => array('draft', 'publish', 'pending', 'future', 'private', 'trash'),
            ),
            'author' => array(
                'name' => 'author',
                'type' => 'integer',
                'description' => 'Author ID',
                'readonly' => false,
            ),
            'date' => array(
                'name' => 'date',
                'type' => 'datetime',
                'description' => 'Post date',
                'readonly' => false,
            ),
            'modified' => array(
                'name' => 'modified',
                'type' => 'datetime',
                'description' => 'Last modified date',
                'readonly' => true,
            ),
            'slug' => array(
                'name' => 'slug',
                'type' => 'string',
                'description' => 'Post slug',
                'readonly' => false,
            ),
            'parent' => array(
                'name' => 'parent',
                'type' => 'integer',
                'description' => 'Parent post ID',
                'readonly' => false,
            ),
        );
    }

    /**
     * Get meta fields for a post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array
     */
    protected function getMetaFields($postType)
    {
        global $wpdb;

        $cacheKey = 'codeia_meta_fields_' . $postType;

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Get meta keys for this post type
        $metaKeys = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT pm.meta_key
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND pm.meta_key NOT LIKE %s
            GROUP BY pm.meta_key
            ORDER BY pm.meta_key",
            $postType,
            '_%'
        ));

        $fields = array();

        foreach ($metaKeys as $key) {
            $fields[$key] = array(
                'name' => $key,
                'type' => 'mixed',
                'description' => '',
                'source' => 'meta',
            );
        }

        $this->cache->set($cacheKey, $fields, 3600);

        return $fields;
    }

    /**
     * Get taxonomy fields for a post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array
     */
    protected function getTaxonomyFields($postType)
    {
        $taxonomies = get_object_taxonomies($postType, 'objects');

        $fields = array();

        foreach ($taxonomies as $taxonomy) {
            if (!$taxonomy->public && !$taxonomy->publicly_queryable) {
                continue;
            }

            $fields[$taxonomy->name] = array(
                'name' => $taxonomy->name,
                'type' => 'taxonomy',
                'description' => $taxonomy->description,
                'hierarchical' => $taxonomy->hierarchical,
                'source' => 'taxonomy',
            );
        }

        return $fields;
    }

    /**
     * Get ACF fields for a post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array
     */
    protected function getACFFields($postType)
    {
        if (!class_exists('ACF')) {
            return array();
        }

        $fieldGroups = acf_get_field_groups(array(
            'post_type' => $postType,
        ));

        $fields = array();

        foreach ($fieldGroups as $group) {
            $groupFields = acf_get_fields($group['key']);

            if ($groupFields) {
                foreach ($groupFields as $field) {
                    $fields[$field['name']] = array(
                        'name' => $field['name'],
                        'label' => $field['label'],
                        'type' => $field['type'],
                        'required' => isset($field['required']) ? (bool) $field['required'] : false,
                        'source' => 'acf',
                        'key' => $field['key'],
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * Get JetEngine fields for a post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array
     */
    protected function getJetEngineFields($postType)
    {
        if (!class_exists('Jet_Engine')) {
            return array();
        }

        $fields = array();

        // Check if JetEngine has meta boxes
        if (class_exists('Jet_Engine\Meta_Boxes')) {
            $metaBoxes = \Jet_Engine\Meta_Boxes::instance()->get_meta_boxes_for_post_type($postType);

            foreach ($metaBoxes as $metaBox) {
                foreach ($metaBox->get_fields() as $field) {
                    $fields[$field['name']] = array(
                        'name' => $field['name'],
                        'label' => $field['title'],
                        'type' => $field['type'],
                        'source' => 'jet_engine',
                    );
                }
            }
        }

        return $fields;
    }

    /**
     * Get MetaBox plugin fields for a post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array
     */
    protected function getMetaBoxFields($postType)
    {
        if (!defined('RWMB_VER')) {
            return array();
        }

        $metaBoxes = rwmb_get_registry('meta_box')->by_post_type($postType);

        $fields = array();

        foreach ($metaBoxes as $metaBox) {
            foreach ($metaBox->fields as $field) {
                $fields[$field['id']] = array(
                    'name' => $field['id'],
                    'label' => $field['name'],
                    'type' => $field['type'],
                    'source' => 'metabox',
                );
            }
        }

        return $fields;
    }

    /**
     * Get all fields (combined) for a post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @param bool   $refresh  Force refresh cache.
     * @return array
     */
    public function getAllFields($postType, $refresh = false)
    {
        $detected = $this->detectForPostType($postType, $refresh);

        $all = array();

        foreach ($detected as $category => $fields) {
            foreach ($fields as $key => $field) {
                $all[$key] = $field;
            }
        }

        return $all;
    }

    /**
     * Get field schema for API documentation.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array
     */
    public function getFieldSchema($postType)
    {
        $fields = $this->getAllFields($postType);

        $schema = array(
            'type' => 'object',
            'properties' => array(),
        );

        foreach ($fields as $key => $field) {
            $schema['properties'][$key] = $this->fieldToSchema($field);
        }

        return $schema;
    }

    /**
     * Convert field to schema format.
     *
     * @since 1.0.0
     *
     * @param array $field Field data.
     * @return array
     */
    protected function fieldToSchema($field)
    {
        $typeMap = array(
            'text' => 'string',
            'textarea' => 'string',
            'number' => 'number',
            'email' => 'string',
            'url' => 'string',
            'image' => 'integer',
            'file' => 'integer',
            'true_false' => 'boolean',
            'select' => 'string',
            'radio' => 'string',
            'checkbox' => 'array',
            'relationship' => 'array',
            'taxonomy' => 'array',
            'datetime' => 'string',
            'date' => 'string',
            'time' => 'string',
        );

        $type = isset($field['type']) && isset($typeMap[$field['type']])
            ? $typeMap[$field['type']]
            : 'string';

        $schema = array(
            'type' => $type,
            'description' => isset($field['description']) ? $field['description'] : '',
        );

        if (isset($field['readonly']) && $field['readonly']) {
            $schema['readOnly'] = true;
        }

        if (isset($field['required']) && $field['required']) {
            // For use in validation
            $schema['required'] = true;
        }

        return $schema;
    }
}
