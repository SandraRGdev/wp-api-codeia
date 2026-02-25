<?php
/**
 * Schema Controller
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\API\Controllers;

use WP_API_Codeia\Schema\Detector;
use WP_API_Codeia\API\ResponseFormatter;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schema Controller.
 *
 * Handles schema discovery and API info endpoints.
 *
 * @since 1.0.0
 */
class SchemaController
{
    /**
     * Schema Detector instance.
     *
     * @since 1.0.0
     *
     * @var Detector
     */
    protected $detector;

    /**
     * Response Formatter instance.
     *
     * @since 1.0.0
     *
     * @var ResponseFormatter
     */
    protected $formatter;

    /**
     * Logger instance.
     *
     * @since 1.0.0
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Create a new Schema Controller instance.
     *
     * @since 1.0.0
     *
     * @param Detector          $detector  Schema Detector.
     * @param ResponseFormatter $formatter Response Formatter.
     * @param Logger            $logger    Logger instance.
     */
    public function __construct(Detector $detector, ResponseFormatter $formatter, Logger $logger)
    {
        $this->detector = $detector;
        $this->formatter = $formatter;
        $this->logger = $logger;
    }

    /**
     * Get full schema.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function getSchema($request)
    {
        $schema = $this->detector->detect();

        // Format for API response
        $response = array(
            'post_types' => array(),
            'taxonomies' => array(),
            'integrations' => $schema['integrations'],
        );

        foreach ($schema['post_types'] as $name => $info) {
            $response['post_types'][$name] = array(
                'slug' => $name,
                'label' => $info['label'],
                'description' => $info['description'],
                'hierarchical' => $info['hierarchical'],
                'rest_base' => $info['rest_base'],
                'supports' => $info['supports'],
                'taxonomies' => $info['taxonomies'],
                'fields' => $this->detector->detectFields($name),
            );
        }

        foreach ($schema['taxonomies'] as $name => $info) {
            $response['taxonomies'][$name] = array(
                'slug' => $name,
                'label' => $info['label'],
                'description' => $info['description'],
                'hierarchical' => $info['hierarchical'],
                'rest_base' => $info['rest_base'],
                'object_type' => $info['object_type'],
            );
        }

        return rest_ensure_response($this->formatter->success($response));
    }

    /**
     * Get post type schema.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function getPostTypeSchema($request)
    {
        $postType = $request->get_param('post_type');

        if (!post_type_exists($postType)) {
            return $this->formatter->notFoundError('Post Type');
        }

        $schema = $this->detector->getPostTypeSchema($postType);

        return rest_ensure_response($this->formatter->success($schema));
    }

    /**
     * Get API info.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function getInfo($request)
    {
        $info = array(
            'name' => 'WP API Codeia',
            'version' => WP_API_CODEIA_VERSION,
            'api_namespace' => WP_API_CODEIA_API_NAMESPACE,
            'api_version' => WP_API_CODEIA_API_VERSION,
            'endpoints' => $this->getEndpointsList(),
            'authentication' => array(
                'methods' => array(WP_API_CODEIA_AUTH_JWT, WP_API_CODEIA_AUTH_API_KEY, WP_API_CODEIA_AUTH_APP_PASSWORD),
            ),
        );

        return rest_ensure_response($this->formatter->success($info));
    }

    /**
     * Get list of available endpoints.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getEndpointsList()
    {
        $schema = $this->detector->detect();
        $endpoints = array();

        foreach ($schema['post_types'] as $postType => $info) {
            $base = $info['rest_base'] ?: $postType;

            $endpoints[] = array(
                'path' => "/v1/{$base}",
                'methods' => array('GET', 'POST'),
                'description' => "List and create {$info['label']}",
            );

            $endpoints[] = array(
                'path' => "/v1/{$base}/{id}",
                'methods' => array('GET', 'PUT', 'DELETE'),
                'description' => "Read, update, delete {$info['label']['singular_name']}",
            );
        }

        return $endpoints;
    }
}
