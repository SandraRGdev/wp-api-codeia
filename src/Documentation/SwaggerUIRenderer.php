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
        add_action('rest_api_init', array($this, 'registerRoutes'));
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
        // OpenAPI JSON endpoint
        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', '/docs', array(
            'methods' => 'GET',
            'callback' => array($this, 'getSpec'),
            'permission_callback' => '__return_true',
        ));

        // Swagger UI endpoint
        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', '/docs/swagger', array(
            'methods' => 'GET',
            'callback' => array($this, 'renderSwagger'),
            'permission_callback' => '__return_true',
        ));

        // ReDoc endpoint
        register_rest_route(WP_API_CODEIA_API_NAMESPACE . '/v1', '/docs/redoc', array(
            'methods' => 'GET',
            'callback' => array($this, 'renderRedoc'),
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
        $refresh = $request->get_param('refresh') === 'true';
        $format = $request->get_param('format') ?: 'json';

        $spec = $this->generator->generate($refresh);

        if ($format === 'yaml') {
            $content = $this->generator->exportYaml();
            $contentType = 'application/yaml';
        } else {
            $content = $this->generator->exportJson(true);
            $contentType = 'application/json';
        }

        return new \WP_REST_Response($content, 200, array('Content-Type' => $contentType));
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
        if ($request->get_param('refresh') === 'true') {
            $specUrl .= '?refresh=true';
        }

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

        if ($request->get_param('refresh') === 'true') {
            $specUrl .= '?refresh=true';
        }

        include $this->getTemplatePath('redoc.php');
        exit;
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

        return WP_API_CODEIA_PLUGIN_DIR . 'templates/' . $template;
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
}
