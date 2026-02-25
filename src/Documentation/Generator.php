<?php
/**
 * OpenAPI Generator
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Documentation;

use WP_API_Codeia\Core\Interfaces\ServiceInterface;
use WP_API_Codeia\Schema\Detector;
use WP_API_Codeia\Utils\Cache\CacheManager;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OpenAPI Generator.
 *
 * Generates OpenAPI 3.0 specification from detected schema.
 *
 * @since 1.0.0
 */
class Generator implements ServiceInterface
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
     * OpenAPI specification version.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $openapiVersion = '3.0.0';

    /**
     * Create a new Generator instance.
     *
     * @since 1.0.0
     *
     * @param Detector $detector Schema Detector.
     * @param CacheManager $cache Cache Manager.
     * @param Logger   $logger   Logger instance.
     */
    public function __construct(Detector $detector, CacheManager $cache, Logger $logger)
    {
        $this->detector = $detector;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Register the generator service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        add_action('wp_api_codeia_schema_cache_cleared', array($this, 'clearCache'));
    }

    /**
     * Boot the generator service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        // No boot needed
    }

    /**
     * Generate OpenAPI specification.
     *
     * @since 1.0.0
     *
     * @param bool $refresh Force refresh cache.
     * @return array
     */
    public function generate($refresh = false)
    {
        $cacheKey = 'codeia_openapi_spec';

        if (!$refresh) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $schema = $this->detector->detect($refresh);

        $spec = array(
            'openapi' => $this->openapiVersion,
            'info' => $this->getInfo(),
            'servers' => $this->getServers(),
            'paths' => $this->generatePaths($schema),
            'components' => $this->generateComponents($schema),
            'tags' => $this->generateTags($schema),
        );

        $this->cache->set($cacheKey, $spec, 3600);

        return $spec;
    }

    /**
     * Get API info.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getInfo()
    {
        return array(
            'title' => 'WP API Codeia',
            'description' => $this->getDescription(),
            'version' => WP_API_CODEIA_VERSION,
            'contact' => array(
                'name' => get_bloginfo('name'),
                'url' => home_url(),
            ),
            'license' => array(
                'name' => 'GPL v2 or later',
                'url' => 'https://www.gnu.org/licenses/gpl-2.0.html',
            ),
        );
    }

    /**
     * Get API description.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected function getDescription()
    {
        $description = __('WordPress REST API with dynamic endpoints for all post types and taxonomies.', 'wp-api-codeia');

        if (function_exists('get_option')) {
            $custom = get_option('codeia_api_description', '');
            if (!empty($custom)) {
                $description = $custom;
            }
        }

        return $description;
    }

    /**
     * Get servers.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getServers()
    {
        $baseUrl = rest_url(WP_API_CODEIA_API_NAMESPACE);

        return array(
            array(
                'url' => str_replace('/' . WP_API_CODEIA_API_NAMESPACE . '/', '/v1/', $baseUrl),
                'description' => 'API v1',
            ),
        );
    }

    /**
     * Generate paths from schema.
     *
     * @since 1.0.0
     *
     * @param array $schema Detected schema.
     * @return array
     */
    protected function generatePaths($schema)
    {
        $paths = array();

        // Generate auth paths
        $paths = array_merge($paths, $this->generateAuthPaths());

        // Generate post type paths
        foreach ($schema['post_types'] as $postType => $info) {
            if (!$info['api_visible']) {
                continue;
            }
            $paths = array_merge($paths, $this->generatePostTypePaths($postType, $info));
        }

        // Generate taxonomy paths
        foreach ($schema['taxonomies'] as $taxonomy => $info) {
            if (!$info['api_visible']) {
                continue;
            }
            $paths = array_merge($paths, $this->generateTaxonomyPaths($taxonomy, $info));
        }

        // Generate schema path
        $paths['/v1/schema'] = $this->getSchemaPath();
        $paths['/v1/schema/{post_type}'] = $this->getPostTypeSchemaPath();

        return $paths;
    }

    /**
     * Generate authentication paths.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function generateAuthPaths()
    {
        return array(
            '/v1/auth/login' => array(
                'post' => array(
                    'summary' => __('Login', 'wp-api-codeia'),
                    'description' => __('Authenticate user and receive access token', 'wp-api-codeia'),
                    'tags' => array('Authentication'),
                    'requestBody' => array(
                        'required' => true,
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'username' => array('type' => 'string'),
                                        'password' => array('type' => 'string', 'format' => 'password'),
                                        'strategy' => array('type' => 'string', 'enum' => array('jwt', 'api_key', 'app_password')),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'responses' => array(
                        '200' => array(
                            'description' => __('Successful login', 'wp-api-codeia'),
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array('$ref' => '#/components/schemas/AuthResponse'),
                                ),
                            ),
                        ),
                        '401' => array('$ref' => '#/components/responses/Unauthorized'),
                    ),
                ),
            ),
            '/v1/auth/refresh' => array(
                'post' => array(
                    'summary' => __('Refresh token', 'wp-api-codeia'),
                    'description' => __('Refresh access token using refresh token', 'wp-api-codeia'),
                    'tags' => array('Authentication'),
                    'requestBody' => array(
                        'required' => true,
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    'type' => 'object',
                                    'required' => array('refresh_token'),
                                    'properties' => array(
                                        'refresh_token' => array('type' => 'string'),
                                    ),
                                ),
                            ),
                        ),
                    ),
                    'responses' => array(
                        '200' => array('$ref' => '#/components/responses/Success'),
                        '401' => array('$ref' => '#/components/responses/Unauthorized'),
                    ),
                ),
            ),
            '/v1/auth/logout' => array(
                'post' => array(
                    'summary' => __('Logout', 'wp-api-codeia'),
                    'description' => __('Revoke authentication tokens', 'wp-api-codeia'),
                    'tags' => array('Authentication'),
                    'security' => array(array('bearerAuth' => array())),
                    'responses' => array(
                        '200' => array('$ref' => '#/components/responses/Success'),
                        '401' => array('$ref' => '#/components/responses/Unauthorized'),
                    ),
                ),
            ),
            '/v1/auth/verify' => array(
                'get' => array(
                    'summary' => __('Verify token', 'wp-api-codeia'),
                    'description' => __('Verify current authentication', 'wp-api-codeia'),
                    'tags' => array('Authentication'),
                    'security' => array(array('bearerAuth' => array())),
                    'responses' => array(
                        '200' => array('$ref' => '#/components/responses/Success'),
                        '401' => array('$ref' => '#/components/responses/Unauthorized'),
                    ),
                ),
            ),
        );
    }

    /**
     * Generate paths for a post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @param array  $info     Post type info.
     * @return array
     */
    protected function generatePostTypePaths($postType, $info)
    {
        $base = $info['rest_base'] ?: $postType;
        $paths = array();

        // Collection endpoints
        $paths["/v1/{$base}"] = array(
            'get' => array(
                'summary' => sprintf(__('List %s', 'wp-api-codeia'), $info['label']['name']),
                'description' => sprintf(__('Retrieve list of %s', 'wp-api-codeia'), strtolower($info['label']['name'])),
                'tags' => array($info['label']['name']),
                'parameters' => $this->getCollectionParameters(),
                'responses' => array(
                    '200' => array('$ref' => '#/components/responses/Collection'),
                    '401' => array('$ref' => '#/components/responses/Unauthorized'),
                    '403' => array('$ref' => '#/components/responses/Forbidden'),
                ),
            ),
            'post' => array(
                'summary' => sprintf(__('Create %s', 'wp-api-codeia'), $info['label']['singular_name']),
                'description' => sprintf(__('Create a new %s', 'wp-api-codeia'), strtolower($info['label']['singular_name'])),
                'tags' => array($info['label']['name']),
                'security' => array(array('bearerAuth' => array())),
                'requestBody' => array(
                    'required' => true,
                    'content' => array(
                        'application/json' => array(
                            'schema' => $this->getPostTypeSchema($postType),
                        ),
                    ),
                ),
                'responses' => array(
                    '201' => array('$ref' => '#/components/responses/Created'),
                    '400' => array('$ref' => '#/components/responses/ValidationError'),
                    '401' => array('$ref' => '#/components/responses/Unauthorized'),
                    '403' => array('$ref' => '#/components/responses/Forbidden'),
                ),
            ),
        );

        // Single item endpoints
        $paths["/v1/{$base}/{id}"] = array(
            'get' => array(
                'summary' => sprintf(__('Get %s', 'wp-api-codeia'), $info['label']['singular_name']),
                'description' => sprintf(__('Retrieve a single %s', 'wp-api-codeia'), strtolower($info['label']['singular_name'])),
                'tags' => array($info['label']['name']),
                'parameters' => array(
                    array(
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => array('type' => 'integer'),
                        'description' => __('Unique identifier', 'wp-api-codeia'),
                    ),
                ),
                'responses' => array(
                    '200' => array('$ref' => '#/components/responses/Success'),
                    '404' => array('$ref' => '#/components/responses/NotFound'),
                ),
            ),
            'put' => array(
                'summary' => sprintf(__('Update %s', 'wp-api-codeia'), $info['label']['singular_name']),
                'description' => sprintf(__('Update a single %s', 'wp-api-codeia'), strtolower($info['label']['singular_name'])),
                'tags' => array($info['label']['name']),
                'security' => array(array('bearerAuth' => array())),
                'parameters' => array(
                    array(
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => array('type' => 'integer'),
                    ),
                ),
                'requestBody' => array(
                    'content' => array(
                        'application/json' => array(
                            'schema' => $this->getPostTypeSchema($postType),
                        ),
                    ),
                ),
                'responses' => array(
                    '200' => array('$ref' => '#/components/responses/Success'),
                    '400' => array('$ref' => '#/components/responses/ValidationError'),
                    '401' => array('$ref' => '#/components/responses/Unauthorized'),
                    '403' => array('$ref' => '#/components/responses/Forbidden'),
                    '404' => array('$ref' => '#/components/responses/NotFound'),
                ),
            ),
            'delete' => array(
                'summary' => sprintf(__('Delete %s', 'wp-api-codeia'), $info['label']['singular_name']),
                'description' => sprintf(__('Delete a single %s', 'wp-api-codeia'), strtolower($info['label']['singular_name'])),
                'tags' => array($info['label']['name']),
                'security' => array(array('bearerAuth' => array())),
                'parameters' => array(
                    array(
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => array('type' => 'integer'),
                    ),
                    array(
                        'name' => 'force',
                        'in' => 'query',
                        'schema' => array('type' => 'boolean'),
                        'description' => __('Bypass trash', 'wp-api-codeia'),
                    ),
                ),
                'responses' => array(
                    '200' => array('$ref' => '#/components/responses/Deleted'),
                    '401' => array('$ref' => '#/components/responses/Unauthorized'),
                    '403' => array('$ref' => '#/components/responses/Forbidden'),
                    '404' => array('$ref' => '#/components/responses/NotFound'),
                ),
            ),
        );

        return $paths;
    }

    /**
     * Generate paths for a taxonomy.
     *
     * @since 1.0.0
     *
     * @param string $taxonomy Taxonomy slug.
     * @param array  $info     Taxonomy info.
     * @return array
     */
    protected function generateTaxonomyPaths($taxonomy, $info)
    {
        $base = $info['rest_base'] ?: $taxonomy;
        $paths = array();

        $paths["/v1/{$base}"] = array(
            'get' => array(
                'summary' => sprintf(__('List %s', 'wp-api-codeia'), $info['label']['name']),
                'tags' => array($info['label']['name']),
                'parameters' => $this->getCollectionParameters(),
                'responses' => array(
                    '200' => array('$ref' => '#/components/responses/Collection'),
                ),
            ),
            'post' => array(
                'summary' => sprintf(__('Create %s', 'wp-api-codeia'), $info['label']['singular_name']),
                'tags' => array($info['label']['name']),
                'security' => array(array('bearerAuth' => array())),
                'requestBody' => array(
                    'required' => true,
                    'content' => array(
                        'application/json' => array(
                            'schema' => $this->getTaxonomySchema($taxonomy),
                        ),
                    ),
                ),
                'responses' => array(
                    '201' => array('$ref' => '#/components/responses/Created'),
                    '401' => array('$ref' => '#/components/responses/Unauthorized'),
                    '403' => array('$ref' => '#/components/responses/Forbidden'),
                ),
            ),
        );

        $paths["/v1/{$base}/{id}"] = array(
            'get' => array(
                'summary' => sprintf(__('Get %s', 'wp-api-codeia'), $info['label']['singular_name']),
                'tags' => array($info['label']['name']),
                'parameters' => array(
                    array(
                        'name' => 'id',
                        'in' => 'path',
                        'required' => true,
                        'schema' => array('type' => 'integer'),
                    ),
                ),
                'responses' => array(
                    '200' => array('$ref' => '#/components/responses/Success'),
                    '404' => array('$ref' => '#/components/responses/NotFound'),
                ),
            ),
            'put' => array(
                'summary' => sprintf(__('Update %s', 'wp-api-codeia'), $info['label']['singular_name']),
                'tags' => array($info['label']['name']),
                'security' => array(array('bearerAuth' => array())),
                'responses' => array(
                    '200' => array('$ref' => '#/components/responses/Success'),
                    '404' => array('$ref' => '#/components/responses/NotFound'),
                ),
            ),
            'delete' => array(
                'summary' => sprintf(__('Delete %s', 'wp-api-codeia'), $info['label']['singular_name']),
                'tags' => array($info['label']['name']),
                'security' => array(array('bearerAuth' => array())),
                'responses' => array(
                    '200' => array('$ref' => '#/components/responses/Deleted'),
                    '404' => array('$ref' => '#/components/responses/NotFound'),
                ),
            ),
        );

        return $paths;
    }

    /**
     * Generate components.
     *
     * @since 1.0.0
     *
     * @param array $schema Detected schema.
     * @return array
     */
    protected function generateComponents($schema)
    {
        return array(
            'securitySchemes' => array(
                'bearerAuth' => array(
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                    'description' => __('JWT Authentication', 'wp-api-codeia'),
                ),
                'apiKeyAuth' => array(
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'X-API-Key',
                    'description' => __('API Key Authentication', 'wp-api-codeia'),
                ),
            ),
            'schemas' => $this->generateSchemas($schema),
            'responses' => $this->generateResponseTemplates(),
            'parameters' => $this->generateParameterTemplates(),
        );
    }

    /**
     * Generate schemas.
     *
     * @since 1.0.0
     *
     * @param array $schema Detected schema.
     * @return array
     */
    protected function generateSchemas($schema)
    {
        $schemas = array(
            'Success' => array(
                'type' => 'object',
                'properties' => array(
                    'success' => array('type' => 'boolean'),
                    'data' => array('type' => 'object'),
                    'meta' => array('$ref' => '#/components/schemas/Meta'),
                ),
            ),
            'AuthResponse' => array(
                'type' => 'object',
                'properties' => array(
                    'success' => array('type' => 'boolean'),
                    'data' => array(
                        'type' => 'object',
                        'properties' => array(
                            'user' => array('$ref' => '#/components/schemas/User'),
                            'tokens' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'access_token' => array('type' => 'string'),
                                    'refresh_token' => array('type' => 'string'),
                                    'token_type' => array('type' => 'string'),
                                    'expires_in' => array('type' => 'integer'),
                                ),
                            ),
                        ),
                    ),
                    'meta' => array('$ref' => '#/components/schemas/Meta'),
                ),
            ),
            'User' => array(
                'type' => 'object',
                'properties' => array(
                    'id' => array('type' => 'integer'),
                    'username' => array('type' => 'string'),
                    'email' => array('type' => 'string', 'format' => 'email'),
                    'roles' => array('type' => 'array', 'items' => array('type' => 'string')),
                ),
            ),
            'Meta' => array(
                'type' => 'object',
                'properties' => array(
                    'timestamp' => array('type' => 'string', 'format' => 'date-time'),
                    'request_id' => array('type' => 'string', 'format' => 'uuid'),
                    'version' => array('type' => 'string'),
                ),
            ),
            'Error' => array(
                'type' => 'object',
                'properties' => array(
                    'success' => array('type' => 'boolean', 'example' => false),
                    'error' => array(
                        'type' => 'object',
                        'properties' => array(
                            'code' => array('type' => 'string'),
                            'message' => array('type' => 'string'),
                        ),
                    ),
                ),
            ),
            'Pagination' => array(
                'type' => 'object',
                'properties' => array(
                    'current_page' => array('type' => 'integer'),
                    'per_page' => array('type' => 'integer'),
                    'total_items' => array('type' => 'integer'),
                    'total_pages' => array('type' => 'integer'),
                ),
            ),
        );

        // Add post type schemas
        foreach ($schema['post_types'] as $postType => $info) {
            $schemas[$this->classifyName($postType)] = $this->getPostTypeSchemaDefinition($postType, $info);
        }

        return $schemas;
    }

    /**
     * Get post type schema for request body.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array
     */
    protected function getPostTypeSchema($postType)
    {
        $fields = $this->detector->getFieldDetector()->getAllFields($postType);

        $properties = array();

        foreach ($fields as $key => $field) {
            if (isset($field['source']) && $field['source'] === 'meta') {
                $properties[$key] = array('type' => 'string');
            } else {
                switch ($key) {
                    case 'title':
                        $properties[$key] = array('type' => 'string');
                        break;
                    case 'content':
                    case 'excerpt':
                        $properties[$key] = array('type' => 'string');
                        break;
                    case 'status':
                        $properties[$key] = array('type' => 'string', 'enum' => array('draft', 'publish', 'pending', 'future', 'private'));
                        break;
                    case 'author':
                        $properties[$key] = array('type' => 'integer');
                        break;
                }
            }
        }

        return array(
            'type' => 'object',
            'properties' => $properties,
        );
    }

    /**
     * Get taxonomy schema.
     *
     * @since 1.0.0
     *
     * @param string $taxonomy Taxonomy slug.
     * @return array
     */
    protected function getTaxonomySchema($taxonomy)
    {
        return array(
            'type' => 'object',
            'required' => array('name'),
            'properties' => array(
                'name' => array('type' => 'string'),
                'description' => array('type' => 'string'),
                'slug' => array('type' => 'string'),
                'parent' => array('type' => 'integer'),
            ),
        );
    }

    /**
     * Get post type schema definition.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @param array  $info     Post type info.
     * @return array
     */
    protected function getPostTypeSchemaDefinition($postType, $info)
    {
        return array(
            'type' => 'object',
            'properties' => array(
                'id' => array('type' => 'integer'),
                'title' => array('type' => 'object'),
                'content' => array('type' => 'object'),
                'excerpt' => array('type' => 'object'),
                'status' => array('type' => 'string'),
                'author' => array('type' => 'integer'),
                'date' => array('type' => 'string', 'format' => 'date-time'),
                'modified' => array('type' => 'string', 'format' => 'date-time'),
                'link' => array('type' => 'string', 'format' => 'uri'),
                'meta' => array('type' => 'object'),
            ),
        );
    }

    /**
     * Generate response templates.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function generateResponseTemplates()
    {
        return array(
            'Success' => array(
                'description' => __('Successful response', 'wp-api-codeia'),
                'content' => array(
                    'application/json' => array(
                        'schema' => array('$ref' => '#/components/schemas/Success'),
                    ),
                ),
            ),
            'Created' => array(
                'description' => __('Resource created', 'wp-api-codeia'),
                'content' => array(
                    'application/json' => array(
                        'schema' => array('$ref' => '#/components/schemas/Success'),
                    ),
                ),
            ),
            'Deleted' => array(
                'description' => __('Resource deleted', 'wp-api-codeia'),
                'content' => array(
                    'application/json' => array(
                        'schema' => array('$ref' => '#/components/schemas/Success'),
                    ),
                ),
            ),
            'Collection' => array(
                'description' => __('Collection response', 'wp-api-codeia'),
                'content' => array(
                    'application/json' => array(
                        'schema' => array(
                            'type' => 'object',
                            'properties' => array(
                                'success' => array('type' => 'boolean'),
                                'data' => array(
                                    'type' => 'object',
                                    'properties' => array(
                                        'items' => array('type' => 'array'),
                                        'pagination' => array('$ref' => '#/components/schemas/Pagination'),
                                    ),
                                ),
                                'meta' => array('$ref' => '#/components/schemas/Meta'),
                            ),
                        ),
                    ),
                ),
            ),
            'Unauthorized' => array(
                'description' => __('Unauthorized', 'wp-api-codeia'),
                'content' => array(
                    'application/json' => array(
                        'schema' => array('$ref' => '#/components/schemas/Error'),
                    ),
                ),
            ),
            'Forbidden' => array(
                'description' => __('Forbidden', 'wp-api-codeia'),
                'content' => array(
                    'application/json' => array(
                        'schema' => array('$ref' => '#/components/schemas/Error'),
                    ),
                ),
            ),
            'NotFound' => array(
                'description' => __('Resource not found', 'wp-api-codeia'),
                'content' => array(
                    'application/json' => array(
                        'schema' => array('$ref' => '#/components/schemas/Error'),
                    ),
                ),
            ),
            'ValidationError' => array(
                'description' => __('Validation error', 'wp-api-codeia'),
                'content' => array(
                    'application/json' => array(
                        'schema' => array('$ref' => '#/components/schemas/Error'),
                    ),
                ),
            ),
        );
    }

    /**
     * Generate parameter templates.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function generateParameterTemplates()
    {
        return array(
            'IdParam' => array(
                'name' => 'id',
                'in' => 'path',
                'required' => true,
                'schema' => array('type' => 'integer'),
                'description' => __('Unique identifier', 'wp-api-codeia'),
            ),
            'PageParam' => array(
                'name' => 'page',
                'in' => 'query',
                'schema' => array('type' => 'integer', 'default' => 1),
                'description' => __('Page number', 'wp-api-codeia'),
            ),
            'PerPageParam' => array(
                'name' => 'per_page',
                'in' => 'query',
                'schema' => array('type' => 'integer', 'default' => 10, 'maximum' => 100),
                'description' => __('Items per page', 'wp-api-codeia'),
            ),
        );
    }

    /**
     * Generate tags.
     *
     * @since 1.0.0
     *
     * @param array $schema Detected schema.
     * @return array
     */
    protected function generateTags($schema)
    {
        $tags = array(
            array(
                'name' => 'Authentication',
                'description' => __('Authentication endpoints', 'wp-api-codeia'),
            ),
        );

        foreach ($schema['post_types'] as $postType => $info) {
            if ($info['api_visible']) {
                $tags[] = array(
                    'name' => $info['label']['name'],
                    'description' => $info['description'],
                );
            }
        }

        foreach ($schema['taxonomies'] as $taxonomy => $info) {
            if ($info['api_visible']) {
                $tags[] = array(
                    'name' => $info['label']['name'],
                    'description' => $info['description'],
                );
            }
        }

        return $tags;
    }

    /**
     * Get collection parameters.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getCollectionParameters()
    {
        return array(
            array(
                'name' => 'page',
                'in' => 'query',
                'schema' => array('type' => 'integer', 'default' => 1),
                'description' => __('Page number', 'wp-api-codeia'),
            ),
            array(
                'name' => 'per_page',
                'in' => 'query',
                'schema' => array('type' => 'integer', 'default' => 10, 'maximum' => 100),
                'description' => __('Items per page', 'wp-api-codeia'),
            ),
            array(
                'name' => 'search',
                'in' => 'query',
                'schema' => array('type' => 'string'),
                'description' => __('Search terms', 'wp-api-codeia'),
            ),
            array(
                'name' => 'orderby',
                'in' => 'query',
                'schema' => array('type' => 'string', 'enum' => array('date', 'modified', 'title', 'slug')),
                'description' => __('Sort by field', 'wp-api-codeia'),
            ),
            array(
                'name' => 'order',
                'in' => 'query',
                'schema' => array('type' => 'string', 'enum' => array('asc', 'desc')),
                'description' => __('Sort order', 'wp-api-codeia'),
            ),
        );
    }

    /**
     * Get schema path definition.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getSchemaPath()
    {
        return array(
            'get' => array(
                'summary' => __('Get API Schema', 'wp-api-codeia'),
                'description' => __('Retrieve complete OpenAPI schema', 'wp-api-codeia'),
                'tags' => array('Schema'),
                'responses' => array(
                    '200' => array(
                        'description' => __('OpenAPI specification', 'wp-api-codeia'),
                        'content' => array(
                            'application/json' => array(
                                'schema' => array('type' => 'object'),
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Get post type schema path.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getPostTypeSchemaPath()
    {
        return array(
            'get' => array(
                'summary' => __('Get Post Type Schema', 'wp-api-codeia'),
                'description' => __('Retrieve schema for specific post type', 'wp-api-codeia'),
                'tags' => array('Schema'),
                'parameters' => array(
                    array(
                        'name' => 'post_type',
                        'in' => 'path',
                        'required' => true,
                        'schema' => array('type' => 'string'),
                        'description' => __('Post type slug', 'wp-api-codeia'),
                    ),
                ),
                'responses' => array(
                    '200' => array('$ref' => '#/components/responses/Success'),
                    '404' => array('$ref' => '#/components/responses/NotFound'),
                ),
            ),
        );
    }

    /**
     * Convert string to class name format.
     *
     * @since 1.0.0
     *
     * @param string $string Input string.
     * @return string
     */
    protected function classifyName($string)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    /**
     * Export specification as JSON.
     *
     * @since 1.0.0
     *
     * @param bool $pretty Pretty print JSON.
     * @return string
     */
    public function exportJson($pretty = true)
    {
        $spec = $this->generate();

        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }

        return wp_json_encode($spec, $options);
    }

    /**
     * Export specification as YAML.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function exportYaml()
    {
        // Use Symfony YAML component if available, otherwise return JSON
        if (class_exists('Symfony\Component\Yaml\Yaml')) {
            return \Symfony\Component\Yaml\Yaml::dump($this->generate(), 2);
        }

        return $this->exportJson(true);
    }

    /**
     * Clear cache.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function clearCache()
    {
        $this->cache->delete('codeia_openapi_spec');
    }

    /**
     * Get specification.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getSpecification()
    {
        return $this->generate();
    }
}
