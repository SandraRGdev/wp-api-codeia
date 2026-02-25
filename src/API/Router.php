<?php
/**
 * API Router
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\API;

use WP_API_Codeia\Core\Interfaces\ServiceInterface;
use WP_API_Codeia\Core\Container;
use WP_API_Codeia\Schema\Detector;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API Router.
 *
 * Dynamically registers REST API routes for detected post types
 * and taxonomies.
 *
 * @since 1.0.0
 */
class Router implements ServiceInterface
{
    /**
     * Container instance.
     *
     * @since 1.0.0
     *
     * @var Container
     */
    protected $container;

    /**
     * Schema Detector instance.
     *
     * @since 1.0.0
     *
     * @var Detector
     */
    protected $detector;

    /**
     * Logger instance.
     *
     * @since 1.0.0
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Registered routes.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $routes = array();

    /**
     * Create a new API Router instance.
     *
     * @since 1.0.0
     *
     * @param Container $container DI Container.
     * @param Detector  $detector  Schema Detector.
     * @param Logger    $logger    Logger instance.
     */
    public function __construct(Container $container, Detector $detector, Logger $logger)
    {
        $this->container = $container;
        $this->detector = $detector;
        $this->logger = $logger;
    }

    /**
     * Register the router service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        // Routes will be registered in boot()
    }

    /**
     * Boot the router service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        add_action('rest_api_init', array($this, 'registerRoutes'));
    }

    /**
     * Register all REST API routes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function registerRoutes()
    {
        $schema = $this->detector->detect();

        foreach ($schema['post_types'] as $postType => $info) {
            if ($this->isPostTypeEnabled($postType)) {
                $this->registerPostTypeRoutes($postType, $info);
            }
        }

        foreach ($schema['taxonomies'] as $taxonomy => $info) {
            if ($this->isTaxonomyEnabled($taxonomy)) {
                $this->registerTaxonomyRoutes($taxonomy, $info);
            }
        }

        // Register schema endpoint
        $this->registerSchemaRoutes();

        // Register utility routes
        $this->registerUtilityRoutes();

        $this->logger->debug('Routes registered', array(
            'count' => count($this->routes),
        ));
    }

    /**
     * Register routes for a post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @param array  $info     Post type info.
     * @return void
     */
    protected function registerPostTypeRoutes($postType, $info)
    {
        $base = $this->getPostTypeBase($postType);

        // Collection routes
        $this->registerRoute("/{$base}", array(
            'methods' => 'GET',
            'callback' => array($this, 'getItems'),
            'permission_callback' => array($this, 'getItemsPermission'),
            'args' => $this->getCollectionArgs(),
        ), $postType);

        $this->registerRoute("/{$base}", array(
            'methods' => 'POST',
            'callback' => array($this, 'createItem'),
            'permission_callback' => array($this, 'createItemPermission'),
            'args' => $this->getItemArgs($postType),
        ), $postType);

        // Single item routes
        $this->registerRoute("/{$base}/(?P<id>[\d]+)", array(
            'methods' => 'GET',
            'callback' => array($this, 'getItem'),
            'permission_callback' => array($this, 'getItemPermission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Unique identifier',
                ),
            ),
        ), $postType);

        $this->registerRoute("/{$base}/(?P<id>[\d]+)", array(
            'methods' => 'PUT',
            'callback' => array($this, 'updateItem'),
            'permission_callback' => array($this, 'updateItemPermission'),
            'args' => $this->getItemArgs($postType),
        ), $postType);

        $this->registerRoute("/{$base}/(?P<id>[\d]+)", array(
            'methods' => 'DELETE',
            'callback' => array($this, 'deleteItem'),
            'permission_callback' => array($this, 'deleteItemPermission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                    'description' => 'Unique identifier',
                ),
                'force' => array(
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Whether to bypass trash',
                ),
            ),
        ), $postType);

        // Meta routes
        $this->registerRoute("/{$base}/(?P<id>[\d]+)/meta", array(
            'methods' => 'GET',
            'callback' => array($this, 'getItemMeta'),
            'permission_callback' => array($this, 'getItemPermission'),
        ), $postType);

        $this->registerRoute("/{$base}/(?P<id>[\d]+)/meta", array(
            'methods' => 'POST',
            'callback' => array($this, 'updateItemMeta'),
            'permission_callback' => array($this, 'updateItemPermission'),
        ), $postType);

        // Terms routes
        $this->registerRoute("/{$base}/(?P<id>[\d]+)/terms/(?P<taxonomy>[\w-]+)", array(
            'methods' => 'GET',
            'callback' => array($this, 'getItemTerms'),
            'permission_callback' => array($this, 'getItemPermission'),
        ), $postType);

        $this->registerRoute("/{$base}/(?P<id>[\d]+)/terms/(?P<taxonomy>[\w-]+)", array(
            'methods' => 'POST',
            'callback' => array($this, 'setItemTerms'),
            'permission_callback' => array($this, 'updateItemPermission'),
        ), $postType);
    }

    /**
     * Register routes for a taxonomy.
     *
     * @since 1.0.0
     *
     * @param string $taxonomy Taxonomy slug.
     * @param array  $info     Taxonomy info.
     * @return void
     */
    protected function registerTaxonomyRoutes($taxonomy, $info)
    {
        $base = $this->getTaxonomyBase($taxonomy);

        // Collection routes
        $this->registerRoute("/{$base}", array(
            'methods' => 'GET',
            'callback' => array($this, 'getTerms'),
            'permission_callback' => '__return_true',
            'args' => $this->getTermsCollectionArgs(),
        ), $taxonomy);

        $this->registerRoute("/{$base}", array(
            'methods' => 'POST',
            'callback' => array($this, 'createTerm'),
            'permission_callback' => array($this, 'createTermPermission'),
            'args' => $this->getTermArgs(),
        ), $taxonomy);

        // Single term routes
        $this->registerRoute("/{$base}/(?P<id>[\d]+)", array(
            'methods' => 'GET',
            'callback' => array($this, 'getTerm'),
            'permission_callback' => '__return_true',
        ), $taxonomy);

        $this->registerRoute("/{$base}/(?P<id>[\d]+)", array(
            'methods' => 'PUT',
            'callback' => array($this, 'updateTerm'),
            'permission_callback' => array($this, 'updateTermPermission'),
            'args' => $this->getTermArgs(),
        ), $taxonomy);

        $this->registerRoute("/{$base}/(?P<id>[\d]+)", array(
            'methods' => 'DELETE',
            'callback' => array($this, 'deleteTerm'),
            'permission_callback' => array($this, 'deleteTermPermission'),
        ), $taxonomy);
    }

    /**
     * Register schema routes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function registerSchemaRoutes()
    {
        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', '/schema', array(
            'methods' => 'GET',
            'callback' => array($this, 'getSchema'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', '/schema/(?P<post_type>[\w-]+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'getPostTypeSchema'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Register utility routes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function registerUtilityRoutes()
    {
        // Info endpoint
        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', '/info', array(
            'methods' => 'GET',
            'callback' => array($this, 'getInfo'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Register a single route.
     *
     * @since 1.0.0
     *
     * @param string $route  Route path.
     * @param array  $args    Route arguments.
     * @param string $context Post type or taxonomy.
     * @return bool
     */
    protected function registerRoute($route, $args, $context)
    {
        $fullRoute = '/' . WP_API_CODEIA_API_NAMESPACE . '/v1' . $route;

        // Wrap callbacks to pass context
        $args = $this->wrapCallbacks($args, $context);

        $registered = register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', $route, $args);

        if ($registered) {
            $this->routes[] = array(
                'route' => $fullRoute,
                'context' => $context,
                'methods' => $args['methods'],
            );
        }

        return $registered;
    }

    /**
     * Wrap callbacks to include context.
     *
     * @since 1.0.0
     *
     * @param array  $args    Route arguments.
     * @param string $context Post type or taxonomy.
     * @return array
     */
    protected function wrapCallbacks($args, $context)
    {
        $args['context'] = $context;

        foreach (array('callback', 'permission_callback') as $key) {
            if (isset($args[$key]) && is_array($args[$key])) {
                $args[$key] = function ($request) use ($args, $key, $context) {
                    return call_user_func($args[$key], $request, $context);
                };
            }
        }

        return $args;
    }

    /**
     * Get post type REST base.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return string
     */
    protected function getPostTypeBase($postType)
    {
        $postTypeObject = get_post_type_object($postType);

        if ($postTypeObject && !empty($postTypeObject->rest_base)) {
            return $postTypeObject->rest_base;
        }

        return $postType;
    }

    /**
     * Get taxonomy REST base.
     *
     * @since 1.0.0
     *
     * @param string $taxonomy Taxonomy slug.
     * @return string
     */
    protected function getTaxonomyBase($taxonomy)
    {
        $taxonomyObject = get_taxonomy($taxonomy);

        if ($taxonomyObject && !empty($taxonomyObject->rest_base)) {
            return $taxonomyObject->rest_base;
        }

        return $taxonomy;
    }

    /**
     * Check if post type is enabled for API.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return bool
     */
    protected function isPostTypeEnabled($postType)
    {
        $enabled = wp_api_codeia_config('post_types.' . $postType . '.enabled', true);

        return apply_filters('wp_api_codeia_post_type_enabled', $enabled, $postType);
    }

    /**
     * Check if taxonomy is enabled for API.
     *
     * @since 1.0.0
     *
     * @param string $taxonomy Taxonomy slug.
     * @return bool
     */
    protected function isTaxonomyEnabled($taxonomy)
    {
        $enabled = wp_api_codeia_config('taxonomies.' . $taxonomy . '.enabled', true);

        return apply_filters('wp_api_codeia_taxonomy_enabled', $enabled, $taxonomy);
    }

    /**
     * Get collection arguments.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getCollectionArgs()
    {
        return array(
            'page' => array(
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
                'description' => 'Current page',
            ),
            'per_page' => array(
                'type' => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100,
                'description' => 'Items per page',
            ),
            'search' => array(
                'type' => 'string',
                'description' => 'Search terms',
            ),
            'orderby' => array(
                'type' => 'string',
                'default' => 'date',
                'enum' => array('date', 'modified', 'title', 'slug', 'author', 'id'),
                'description' => 'Sort field',
            ),
            'order' => array(
                'type' => 'string',
                'default' => 'desc',
                'enum' => array('asc', 'desc'),
                'description' => 'Sort order',
            ),
            'status' => array(
                'type' => 'string',
                'default' => 'publish',
                'description' => 'Post status',
            ),
        );
    }

    /**
     * Get item arguments.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array
     */
    protected function getItemArgs($postType)
    {
        return array(
            'title' => array(
                'type' => 'string',
                'description' => 'Post title',
            ),
            'content' => array(
                'type' => 'string',
                'description' => 'Post content',
            ),
            'excerpt' => array(
                'type' => 'string',
                'description' => 'Post excerpt',
            ),
            'status' => array(
                'type' => 'string',
                'description' => 'Post status',
            ),
            'author' => array(
                'type' => 'integer',
                'description' => 'Author ID',
            ),
            'slug' => array(
                'type' => 'string',
                'description' => 'Post slug',
            ),
        );
    }

    /**
     * Get terms collection arguments.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getTermsCollectionArgs()
    {
        return array(
            'page' => array(
                'type' => 'integer',
                'default' => 1,
            ),
            'per_page' => array(
                'type' => 'integer',
                'default' => 10,
                'maximum' => 100,
            ),
            'search' => array(
                'type' => 'string',
            ),
            'orderby' => array(
                'type' => 'string',
                'default' => 'name',
            ),
            'order' => array(
                'type' => 'string',
                'default' => 'asc',
            ),
            'hide_empty' => array(
                'type' => 'boolean',
                'default' => false,
            ),
        );
    }

    /**
     * Get term arguments.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getTermArgs()
    {
        return array(
            'name' => array(
                'type' => 'string',
                'required' => true,
                'description' => 'Term name',
            ),
            'description' => array(
                'type' => 'string',
                'description' => 'Term description',
            ),
            'parent' => array(
                'type' => 'integer',
                'description' => 'Parent term ID',
            ),
            'slug' => array(
                'type' => 'string',
                'description' => 'Term slug',
            ),
        );
    }

    /**
     * Get registered routes.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    // Callback methods - delegated to controllers

    public function getItems($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->getItems($request, $postType);
    }

    public function getItem($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->getItem($request, $postType);
    }

    public function createItem($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->createItem($request, $postType);
    }

    public function updateItem($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->updateItem($request, $postType);
    }

    public function deleteItem($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->deleteItem($request, $postType);
    }

    public function getItemMeta($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->getItemMeta($request, $postType);
    }

    public function updateItemMeta($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->updateItemMeta($request, $postType);
    }

    public function getItemTerms($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->getItemTerms($request, $postType);
    }

    public function setItemTerms($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->setItemTerms($request, $postType);
    }

    public function getTerms($request, $taxonomy)
    {
        $controller = $this->getController('taxonomy');
        return $controller->getTerms($request, $taxonomy);
    }

    public function getTerm($request, $taxonomy)
    {
        $controller = $this->getController('taxonomy');
        return $controller->getTerm($request, $taxonomy);
    }

    public function createTerm($request, $taxonomy)
    {
        $controller = $this->getController('taxonomy');
        return $controller->createTerm($request, $taxonomy);
    }

    public function updateTerm($request, $taxonomy)
    {
        $controller = $this->getController('taxonomy');
        return $controller->updateTerm($request, $taxonomy);
    }

    public function deleteTerm($request, $taxonomy)
    {
        $controller = $this->getController('taxonomy');
        return $controller->deleteTerm($request, $taxonomy);
    }

    public function getSchema($request)
    {
        $controller = $this->getController('schema');
        return $controller->getSchema($request);
    }

    public function getPostTypeSchema($request)
    {
        $controller = $this->getController('schema');
        return $controller->getPostTypeSchema($request);
    }

    public function getInfo($request)
    {
        $controller = $this->getController('schema');
        return $controller->getInfo($request);
    }

    // Permission callbacks

    public function getItemsPermission($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->getItemsPermission($request, $postType);
    }

    public function getItemPermission($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->getItemPermission($request, $postType);
    }

    public function createItemPermission($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->createItemPermission($request, $postType);
    }

    public function updateItemPermission($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->updateItemPermission($request, $postType);
    }

    public function deleteItemPermission($request, $postType)
    {
        $controller = $this->getController('post');
        return $controller->deleteItemPermission($request, $postType);
    }

    public function createTermPermission($request, $taxonomy)
    {
        $controller = $this->getController('taxonomy');
        return $controller->createTermPermission($request, $taxonomy);
    }

    public function updateTermPermission($request, $taxonomy)
    {
        $controller = $this->getController('taxonomy');
        return $controller->updateTermPermission($request, $taxonomy);
    }

    public function deleteTermPermission($request, $taxonomy)
    {
        $controller = $this->getController('taxonomy');
        return $controller->deleteTermPermission($request, $taxonomy);
    }

    /**
     * Get controller instance.
     *
     * @since 1.0.0
     *
     * @param string $type Controller type.
     * @return object
     */
    protected function getController($type)
    {
        $key = 'controller.' . $type;

        if (!$this->container->has($key)) {
            $this->container->singleton($key, function () use ($type) {
                switch ($type) {
                    case 'post':
                        return new Controllers\PostController(
                            $this->container->get('detector'),
                            $this->container->get('response_formatter'),
                            $this->container->get('logger')
                        );
                    case 'taxonomy':
                        return new Controllers\TaxonomyController(
                            $this->container->get('detector'),
                            $this->container->get('response_formatter'),
                            $this->container->get('logger')
                        );
                    case 'schema':
                        return new Controllers\SchemaController(
                            $this->container->get('detector'),
                            $this->container->get('response_formatter'),
                            $this->container->get('logger')
                        );
                    default:
                        return null;
                }
            });
        }

        return $this->container->get($key);
    }
}
