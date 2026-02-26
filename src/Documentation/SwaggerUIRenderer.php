<?php
/**
 * Swagger UI Renderer
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Documentation;

use WP_API_Codeia\Core\Interfaces\ServiceInterface;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Swagger UI Renderer.
 *
 * Renders Swagger UI interface for API documentation.
 *
 * @since 1.0.0
 */
class SwaggerUIRenderer implements ServiceInterface
{
    /**
     * Logger instance.
     *
     * @since 1.0.0
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Generator instance.
     *
     * @since 1.0.0
     *
     * @var Generator
     */
    protected $generator;

    /**
     * Swagger UI version.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $swaggerVersion = '5.0.0';

    /**
     * Create a new Swagger UI Renderer instance.
     *
     * @since 1.0.0
     *
     * @param Generator $generator OpenAPI Generator.
     * @param Logger    $logger    Logger instance.
     */
    public function __construct(Generator $generator, Logger $logger)
    {
        $this->generator = $generator;
        $this->logger = $logger;
    }

    /**
     * Register the renderer service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        // Routes are registered by ServiceProvider
    }

    /**
     * Boot the renderer service.
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
     * Register documentation routes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function registerRoutes()
    {
        // Test endpoint
        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', '/test', array(
            'methods' => 'GET',
            'callback' => function() {
                return new \WP_REST_Response(array(
                    'status' => 'ok',
                    'message' => 'WP API Codeia is working!',
                    'namespace' => WP_API_CODEIA_API_NAMESPACE,
                    'rest_url' => rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1'),
                ), 200);
            },
            'permission_callback' => '__return_true',
        ));

        // Simple JSON test endpoint (returns valid OpenAPI JSON directly)
        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', '/docs/simple', array(
            'methods' => 'GET',
            'callback' => function() {
                $spec = array(
                    'openapi' => '3.0.0',
                    'info' => array(
                        'title' => 'WP API Codeia',
                        'version' => '1.0.0',
                    ),
                    'servers' => array(
                        array('url' => rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1')),
                    ),
                    'paths' => array(),
                    'components' => array(),
                    'tags' => array(),
                );
                return new \WP_REST_Response($spec, 200, array('Content-Type' => 'application/json'));
            },
            'permission_callback' => '__return_true',
        ));

        // Minimal docs endpoint (for testing)
        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', '/docs/minimal', array(
            'methods' => 'GET',
            'callback' => array($this, 'getMinimalSpec'),
            'permission_callback' => '__return_true',
        ));

        // OpenAPI JSON endpoint
        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', '/docs', array(
            'methods' => 'GET',
            'callback' => array($this, 'getSpec'),
            'permission_callback' => '__return_true',
        ));

        // Swagger UI endpoint
        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', '/docs/swagger', array(
            'methods' => 'GET',
            'callback' => array($this, 'renderSwaggerHtmlEndpoint'),
            'permission_callback' => '__return_true',
        ));

        // ReDoc endpoint
        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', '/docs/redoc', array(
            'methods' => 'GET',
            'callback' => array($this, 'renderRedocHtmlEndpoint'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Get OpenAPI specification.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function getSpec($request)
    {
        try {
            $refreshParam = $request->get_param('refresh');
            $refresh = ($refreshParam === 'true' || $refreshParam === '1' || $refreshParam === true);

            error_log('WP API Codeia: getSpec called with refresh=' . var_export($refreshParam, true) . ' (resolved to ' . var_export($refresh, true) . ')');

            // Build minimal spec directly
            $spec = array(
                'openapi' => '3.0.0',
                'info' => array(
                    'title' => 'WP API Codeia',
                    'version' => '1.0.0',
                    'description' => 'WordPress REST API Documentation',
                ),
                'servers' => array(
                    array(
                        'url' => rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1'),
                        'description' => 'API v1',
                    ),
                ),
                'paths' => array(),
                'components' => array(
                    'securitySchemes' => array(
                        'bearerAuth' => array(
                            'type' => 'http',
                            'scheme' => 'bearer',
                            'bearerFormat' => 'JWT',
                        ),
                    ),
                ),
                'tags' => array(
                    array('name' => 'Diagnostics', 'description' => 'Diagnostic endpoints'),
                ),
            );

            // Add diagnostic endpoints
            $spec['paths']['/v1/test'] = array(
                'get' => array(
                    'summary' => 'Test API',
                    'description' => 'Basic API test endpoint',
                    'tags' => array('Diagnostics'),
                    'responses' => array(
                        '200' => array(
                            'description' => 'Success',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'status' => array('type' => 'string'),
                                            'message' => array('type' => 'string'),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            );

            // Always add post type endpoints (removed refresh check)
            error_log('WP API Codeia: Fetching post types...');
            try {
                $postTypes = get_post_types(array('public' => true, 'show_in_rest' => true), 'objects');
                error_log('WP API Codeia: Found ' . count($postTypes) . ' post types');

                foreach ($postTypes as $pt) {
                    $base = isset($pt->rest_base) && $pt->rest_base ? $pt->rest_base : $pt->name;
                    $name = isset($pt->labels->name) ? $pt->labels->name : $pt->name;

                    $spec['tags'][] = array('name' => $name, 'description' => "{$name} endpoints");

                    $spec['paths']["/v1/{$base}"] = array(
                        'get' => array(
                            'summary' => "List {$name}",
                            'description' => "Retrieve list of {$name}",
                            'tags' => array($name),
                            'responses' => array(
                                '200' => array('description' => 'Success'),
                            ),
                        ),
                    );

                    $spec['paths']["/v1/{$base}/{id}"] = array(
                        'get' => array(
                            'summary' => "Get single {$name}",
                            'tags' => array($name),
                            'parameters' => array(
                                array(
                                    'name' => 'id',
                                    'in' => 'path',
                                    'required' => true,
                                    'schema' => array('type' => 'integer'),
                                    'description' => 'Resource ID',
                                ),
                            ),
                            'responses' => array(
                                '200' => array('description' => 'Success'),
                                '404' => array('description' => 'Not Found'),
                            ),
                        ),
                    );
                }
                error_log('WP API Codeia: Successfully added post types to spec');
            } catch (\Throwable $e) {
                error_log('WP API Codeia: Could not add post types: ' . $e->getMessage());
            }

            error_log('WP API Codeia: Returning spec with ' . count($spec['paths']) . ' paths');

            // Return as REST response with proper JSON headers
            return new \WP_REST_Response($spec, 200, array('Content-Type' => 'application/json'));
        } catch (\Throwable $e) {
            error_log('WP API Codeia: Fatal error in getSpec: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            // Return minimal spec even on fatal error
            return new \WP_REST_Response(array(
                'openapi' => '3.0.0',
                'info' => array(
                    'title' => 'WP API Codeia',
                    'version' => '1.0.0',
                    'description' => 'API Documentation (error mode)',
                ),
                'servers' => array(
                    array('url' => rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1')),
                ),
                'paths' => array(),
                'components' => array(),
                'tags' => array(),
            ), 200, array('Content-Type' => 'application/json'));
        }
    }

    /**
     * Get minimal spec array for fallback.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getMinimalSpecArray()
    {
        return array(
            'openapi' => '3.0.0',
            'info' => array(
                'title' => 'WP API Codeia',
                'version' => '1.0.0',
                'description' => 'API Documentation',
            ),
            'servers' => array(
                array(
                    'url' => rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1'),
                    'description' => 'API v1',
                ),
            ),
            'paths' => array(
                '/v1/test' => array(
                    'get' => array(
                        'summary' => 'Test endpoint',
                        'description' => 'Basic API test endpoint',
                        'responses' => array(
                            '200' => array(
                                'description' => 'Success',
                            ),
                        ),
                    ),
                ),
                '/v1/docs/minimal' => array(
                    'get' => array(
                        'summary' => 'Minimal OpenAPI Spec',
                        'description' => 'Minimal OpenAPI specification',
                        'responses' => array(
                            '200' => array(
                                'description' => 'Success',
                            ),
                        ),
                    ),
                ),
            ),
            'components' => array(),
            'tags' => array(
                array(
                    'name' => 'Diagnostics',
                    'description' => 'Diagnostic endpoints',
                ),
            ),
        );
    }

    /**
     * Render Swagger UI.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return void
     */
    public function renderSwagger($request)
    {
        $specUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs');

        // Check if refresh is requested
        $refreshParam = $request->get_param('refresh');
        if ($refreshParam === 'true' || $refreshParam === '1' || $refreshParam === true) {
            $specUrl .= '?refresh=1';
        }

        // Prepare template variables
        $data = $this->getTemplateData();
        $data['specUrl'] = $specUrl;

        extract($data);
        include $this->getTemplatePath('swagger.php');
        exit;
    }

    /**
     * Render ReDoc.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return void
     */
    public function renderRedoc($request)
    {
        $specUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs');

        $refreshParam = $request->get_param('refresh');
        if ($refreshParam === 'true' || $refreshParam === '1' || $refreshParam === true) {
            $specUrl .= '?refresh=1';
        }

        // Prepare template variables
        $data = $this->getTemplateData();
        $data['specUrl'] = $specUrl;

        extract($data);
        include $this->getTemplatePath('redoc.php');
        exit;
    }

    /**
     * Get Swagger UI HTML as REST response.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response HTML response.
     */
    public function renderSwaggerHtmlEndpoint($request)
    {
        $specUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs');

        // Check if refresh is requested
        $refreshParam = $request->get_param('refresh');
        if ($refreshParam === 'true' || $refreshParam === '1' || $refreshParam === true) {
            $specUrl .= '?refresh=1';
        }

        // Prepare template variables
        $data = $this->getTemplateData();
        $data['specUrl'] = $specUrl;

        // Extract variables for template
        extract($data);

        // Start output buffer
        ob_start();
        include $this->getTemplatePath('swagger.php');
        $html = ob_get_clean();

        // Return as HTML response
        return new \WP_REST_Response($html, 200, array(
            'Content-Type' => 'text/html; charset=UTF-8',
        ));
    }

    /**
     * Get ReDoc HTML as REST response.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response HTML response.
     */
    public function renderRedocHtmlEndpoint($request)
    {
        $specUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs');

        $refreshParam = $request->get_param('refresh');
        if ($refreshParam === 'true' || $refreshParam === '1' || $refreshParam === true) {
            $specUrl .= '?refresh=1';
        }

        // Prepare template variables
        $data = $this->getTemplateData();
        $data['specUrl'] = $specUrl;

        // Extract variables for template
        extract($data);

        // Start output buffer
        ob_start();
        include $this->getTemplatePath('redoc.php');
        $html = ob_get_clean();

        // Return as HTML response
        return new \WP_REST_Response($html, 200, array(
            'Content-Type' => 'text/html; charset=UTF-8',
        ));
    }

    /**
     * Get template path.
     *
     * @since 1.0.0
     *
     * @param string $template Template name.
     * @return string
     */
    protected function getTemplatePath($template)
    {
        $customPath = locate_template('wp-api-codeia/' . $template);

        if ($customPath) {
            return $customPath;
        }

        return WP_API_CODEIA_PLUGIN_DIR . '/templates/' . $template;
    }

    /**
     * Get Swagger UI HTML.
     *
     * @since 1.0.0
     *
     * @param string $specUrl OpenAPI spec URL.
     * @return string
     */
    public function getSwaggerHtml($specUrl)
    {
        $title = __('API Documentation', 'wp-api-codeia');

        ob_start();
        include $this->getTemplatePath('swagger.php');
        return ob_get_clean();
    }

    /**
     * Get ReDoc HTML.
     *
     * @since 1.0.0
     *
     * @param string $specUrl OpenAPI spec URL.
     * @return string
     */
    public function getRedocHtml($specUrl)
    {
        $title = __('API Documentation', 'wp-api-codeia');

        ob_start();
        include $this->getTemplatePath('redoc.php');
        return ob_get_clean();
    }

    /**
     * Get template data.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getTemplateData()
    {
        return array(
            'title' => __('API Documentation', 'wp-api-codeia'),
            'site_name' => get_bloginfo('name'),
            'logo_url' => get_site_icon_url(),
        );
    }

    /**
     * Embed Swagger UI in page content.
     *
     * @since 1.0.0
     *
     * @param string $content Page content.
     * @return string
     */
    public function embedInPage($content)
    {
        if (strpos($content, '[codeia_api_docs]') === false) {
            return $content;
        }

        $specUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs');
        $html = $this->getSwaggerHtml($specUrl);

        return str_replace('[codeia_api_docs]', $html, $content);
    }

    /**
     * Embed ReDoc in page content.
     *
     * @since 1.0.0
     *
     * @param string $content Page content.
     * @return string
     */
    public function embedRedocInPage($content)
    {
        if (strpos($content, '[codeia_api_redoc]') === false) {
            return $content;
        }

        $specUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs');
        $html = $this->getRedocHtml($specUrl);

        return str_replace('[codeia_api_redoc]', $html, $content);
    }

    /**
     * Get minimal OpenAPI spec for testing.
     *
     * @since 1.0.0
     *
     * @return \WP_REST_Response
     */
    public function getMinimalSpec()
    {
        // Return proper REST response with JSON headers
        return new \WP_REST_Response(array(
            'openapi' => '3.0.0',
            'info' => array(
                'title' => 'WP API Codeia',
                'version' => '1.0.0',
                'description' => 'Test endpoint',
            ),
            'servers' => array(
                array(
                    'url' => rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1'),
                    'description' => 'API v1',
                ),
            ),
            'paths' => array(
                '/v1/test' => array(
                    'get' => array(
                        'summary' => 'Test endpoint',
                        'responses' => array(
                            '200' => array(
                                'description' => 'Success',
                            ),
                        ),
                    ),
                ),
            ),
            'components' => array(),
            'tags' => array(),
        ), 200, array('Content-Type' => 'application/json'));
    }
}
