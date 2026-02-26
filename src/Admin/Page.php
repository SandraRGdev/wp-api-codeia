<?php
/**
 * Admin Page
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Admin;

use WP_API_Codeia\Core\Interfaces\ServiceInterface;
use WP_API_Codeia\Core\Container;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Page.
 *
 * Main admin menu and dashboard page.
 *
 * @since 1.0.0
 */
class Page implements ServiceInterface
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
     * Page slug.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $slug = 'wp-api-codeia';

    /**
     * Create a new Page instance.
     *
     * @since 1.0.0
     *
     * @param ?Container $container Optional DI container.
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Register the admin service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        add_action('admin_menu', array($this, 'addMenu'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAssets'));

        // AJAX handlers for API key management
        add_action('wp_ajax_codeia_create_api_key', array($this, 'ajaxCreateApiKey'));
        add_action('wp_ajax_codeia_revoke_api_key', array($this, 'ajaxRevokeApiKey'));
        add_action('wp_ajax_codeia_list_api_keys', array($this, 'ajaxListApiKeys'));

        // AJAX handlers for field configuration
        add_action('wp_ajax_codeia_get_post_type_fields', array($this, 'ajaxGetPostTypeFields'));
        add_action('wp_ajax_codeia_save_field_config', array($this, 'ajaxSaveFieldConfig'));

        // Shortcodes for embedding documentation
        add_shortcode('codeia_api_docs', array($this, 'shortcodeApiDocs'));
        add_shortcode('codeia_api_redoc', array($this, 'shortcodeApiRedoc'));
    }

    /**
     * Boot the admin service.
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
     * Add admin menu.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function addMenu()
    {
        add_menu_page(
            __('WP API Codeia', 'wp-api-codeia'),
            __('API Codeia', 'wp-api-codeia'),
            'manage_options',
            $this->slug,
            array($this, 'renderDashboard'),
            'dashicons-rest-api',
            30
        );

        add_submenu_page(
            $this->slug,
            __('Dashboard', 'wp-api-codeia'),
            __('Dashboard', 'wp-api-codeia'),
            'manage_options',
            $this->slug,
            array($this, 'renderDashboard')
        );

        add_submenu_page(
            $this->slug,
            __('Authentication', 'wp-api-codeia'),
            __('Authentication', 'wp-api-codeia'),
            'manage_options',
            $this->slug . '-auth',
            array($this, 'renderAuthentication')
        );

        add_submenu_page(
            $this->slug,
            __('Endpoints', 'wp-api-codeia'),
            __('Endpoints', 'wp-api-codeia'),
            'manage_options',
            $this->slug . '-endpoints',
            array($this, 'renderEndpoints')
        );

        add_submenu_page(
            $this->slug,
            __('Permissions', 'wp-api-codeia'),
            __('Permissions', 'wp-api-codeia'),
            'manage_options',
            $this->slug . '-permissions',
            array($this, 'renderPermissions')
        );

        add_submenu_page(
            $this->slug,
            __('Upload', 'wp-api-codeia'),
            __('Upload', 'wp-api-codeia'),
            'manage_options',
            $this->slug . '-upload',
            array($this, 'renderUpload')
        );

        add_submenu_page(
            $this->slug,
            __('Documentation', 'wp-api-codeia'),
            __('Documentation', 'wp-api-codeia'),
            'manage_options',
            $this->slug . '-docs',
            array($this, 'renderDocumentation')
        );

        add_submenu_page(
            $this->slug,
            __('Logs', 'wp-api-codeia'),
            __('Logs', 'wp-api-codeia'),
            'manage_options',
            $this->slug . '-logs',
            array($this, 'renderLogs')
        );

        add_submenu_page(
            $this->slug,
            __('Settings', 'wp-api-codeia'),
            __('Settings', 'wp-api-codeia'),
            'manage_options',
            $this->slug . '-settings',
            array($this, 'renderSettings')
        );
    }

    /**
     * Register settings.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function registerSettings()
    {
        // Register settings sections and fields
        add_settings_section(
            'codeia_general',
            __('General Settings', 'wp-api-codeia'),
            null,
            'codeia_general_section'
        );

        add_settings_section(
            'codeia_auth',
            __('Authentication', 'wp-api-codeia'),
            null,
            'codeia_auth_section'
        );

        add_settings_section(
            'codeia_rate_limit',
            __('Rate Limiting', 'wp-api-codeia'),
            null,
            'codeia_rate_limit_section'
        );

        // General settings
        register_setting($this->slug, 'wp_api_codeia_enabled_post_types', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitizePostTypes'),
        ));

        // Auth settings
        register_setting($this->slug, 'wp_api_codeia_auth_config', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitizeAuthConfig'),
        ));

        // Rate limit settings
        register_setting($this->slug, 'wp_api_codeia_rate_limit', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitizeRateLimitConfig'),
        ));
    }

    /**
     * Sanitize post types array.
     *
     * @since 1.0.0
     *
     * @param array $postTypes Raw post types array.
     * @return array Sanitized array.
     */
    public function sanitizePostTypes($postTypes)
    {
        if (!is_array($postTypes)) {
            return array('post', 'page');
        }

        $validPostTypes = get_post_types(array('show_in_rest' => true));
        return array_intersect($postTypes, $validPostTypes);
    }

    /**
     * Sanitize auth config.
     *
     * @since 1.0.0
     *
     * @param array $config Raw config.
     * @return array Sanitized config.
     */
    public function sanitizeAuthConfig($config)
    {
        if (!is_array($config)) {
            return array(
                'default' => 'api_key',
                'jwt_access_ttl' => 3600,
                'jwt_refresh_ttl' => 2592000,
            );
        }

        $validMethods = array('public', 'api_key', 'jwt', 'any');
        $config['default'] = isset($config['default']) && in_array($config['default'], $validMethods)
            ? $config['default']
            : 'api_key';

        $config['jwt_access_ttl'] = isset($config['jwt_access_ttl'])
            ? absint($config['jwt_access_ttl'])
            : 3600;

        $config['jwt_refresh_ttl'] = isset($config['jwt_refresh_ttl'])
            ? absint($config['jwt_refresh_ttl'])
            : 2592000;

        return $config;
    }

    /**
     * Sanitize rate limit config.
     *
     * @since 1.0.0
     *
     * @param array $config Raw config.
     * @return array Sanitized config.
     */
    public function sanitizeRateLimitConfig($config)
    {
        if (!is_array($config)) {
            return array(
                'enabled' => false,
                'requests_per_hour' => 1000,
            );
        }

        $config['enabled'] = !empty($config['enabled']);
        $config['requests_per_hour'] = isset($config['requests_per_hour'])
            ? absint($config['requests_per_hour'])
            : 1000;

        return $config;
    }

    /**
     * Enqueue admin assets.
     *
     * @since 1.0.0
     *
     * @param string $hook Current admin page.
     * @return void
     */
    public function enqueueAssets($hook)
    {
        if (strpos($hook, 'wp-api-codeia') === false) {
            return;
        }

        wp_enqueue_style(
            'wp-api-codeia-admin',
            plugins_url('assets/css/admin.css', WP_API_CODEIA_PLUGIN_FILE),
            array(),
            WP_API_CODEIA_VERSION
        );

        wp_enqueue_script(
            'wp-api-codeia-admin',
            plugins_url('assets/js/admin.js', WP_API_CODEIA_PLUGIN_FILE),
            array('jquery'),
            WP_API_CODEIA_VERSION,
            true
        );

        wp_localize_script('wp-api-codeia-admin', 'codeiaAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('codeia_admin'),
            'restUrl' => rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1'),
        ));
    }

    /**
     * Render dashboard page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function renderDashboard()
    {
        $stats = $this->getDashboardStats();

        include $this->getTemplatePath('dashboard.php');
    }

    /**
     * Render authentication page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function renderAuthentication()
    {
        $activeTokens = $this->getActiveTokensCount();
        $activeApiKeys = $this->getActiveApiKeysCount();

        include $this->getTemplatePath('authentication.php');
    }

    /**
     * Render endpoints page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function renderEndpoints()
    {
        // Get post types
        $postTypes = array();
        foreach (get_post_types(array('show_in_rest' => true), 'objects') as $postType) {
            $postTypes[$postType->name] = array(
                'label' => $postType->label,
                'rest_base' => isset($postType->rest_base) ? $postType->rest_base : $postType->name,
                'api_visible' => in_array($postType->name, array('post', 'page'), true),
            );
        }

        // Get taxonomies
        $taxonomies = array();
        foreach (get_taxonomies(array('show_in_rest' => true), 'objects') as $taxonomy) {
            $taxonomies[$taxonomy->name] = array(
                'label' => $taxonomy->label,
                'rest_base' => isset($taxonomy->rest_base) ? $taxonomy->rest_base : $taxonomy->name,
                'hierarchical' => $taxonomy->hierarchical,
            );
        }

        include $this->getTemplatePath('endpoints.php');
    }

    /**
     * Render permissions page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function renderPermissions()
    {
        $roles = get_editable_roles();
        $matrix = get_option('codeia_permissions', array());

        include $this->getTemplatePath('permissions.php');
    }

    /**
     * Render upload page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function renderUpload()
    {
        $maxFileSize = $this->getMaxUploadSize();
        $allowedMimes = get_allowed_mime_types();

        include $this->getTemplatePath('upload.php');
    }

    /**
     * Render documentation page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function renderDocumentation()
    {
        $specUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs');
        $swaggerUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs/swagger');
        $redocUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs/redoc');

        include $this->getTemplatePath('documentation.php');
    }

    /**
     * Render logs page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function renderLogs()
    {
        $logs = $this->getRecentLogs();

        include $this->getTemplatePath('logs.php');
    }

    /**
     * Render settings page.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function renderSettings()
    {
        include $this->getTemplatePath('settings.php');
    }

    /**
     * Get dashboard statistics.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getDashboardStats()
    {
        global $wpdb;

        $tokensTable = $wpdb->prefix . WP_API_CODEIA_TOKENS_TABLE;
        $apiKeysTable = $wpdb->prefix . WP_API_CODEIA_API_KEYS_TABLE;

        $activeTokens = $wpdb->get_var("SELECT COUNT(*) FROM {$tokensTable} WHERE expires_at > NOW()");
        $activeApiKeys = $wpdb->get_var("SELECT COUNT(*) FROM {$apiKeysTable} WHERE is_revoked = 0 AND (expires_at IS NULL OR expires_at > NOW())");

        return array(
            'active_tokens' => (int) $activeTokens,
            'active_api_keys' => (int) $activeApiKeys,
            'total_requests_today' => $this->getRequestCountToday(),
            'cache_hits' => $this->getCacheHits(),
        );
    }

    /**
     * Get active tokens count.
     *
     * @since 1.0.0
     *
     * @return int
     */
    protected function getActiveTokensCount()
    {
        global $wpdb;

        $table = $wpdb->prefix . WP_API_CODEIA_TOKENS_TABLE;

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE expires_at > NOW()");
    }

    /**
     * Get active API keys count.
     *
     * @since 1.0.0
     *
     * @return int
     */
    protected function getActiveApiKeysCount()
    {
        global $wpdb;

        $table = $wpdb->prefix . WP_API_CODEIA_API_KEYS_TABLE;

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE is_revoked = 0 AND (expires_at IS NULL OR expires_at > NOW())");
    }

    /**
     * Get request count today.
     *
     * @since 1.0.0
     *
     * @return int
     */
    protected function getRequestCountToday()
    {
        // This would be implemented with a request logging table
        return 0;
    }

    /**
     * Get cache hits.
     *
     * @since 1.0.0
     *
     * @return int
     */
    protected function getCacheHits()
    {
        // Get cache from container if available
        if ($this->container && $this->container->has('cache')) {
            $cache = $this->container->get('cache');
            if ($cache && method_exists($cache, 'getStats')) {
                return $cache->getStats();
            }
        }

        return 0;
    }

    /**
     * Get recent logs.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getRecentLogs()
    {
        // Get logger from container if available
        if ($this->container && $this->container->has('logger')) {
            $logger = $this->container->get('logger');
            if ($logger && method_exists($logger, 'getLogs')) {
                return $logger->getLogs(50);
            }
        }

        return array();
    }

    /**
     * Get max upload size.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getMaxUploadSize()
    {
        $uploadMax = $this->convertToBytes(ini_get('upload_max_filesize'));
        $postMax = $this->convertToBytes(ini_get('post_max_size'));

        return array(
            'bytes' => min($uploadMax, $postMax),
            'formatted' => size_format(min($uploadMax, $postMax)),
        );
    }

    /**
     * Convert shorthand to bytes.
     *
     * @since 1.0.0
     *
     * @param string $value Shorthand value.
     * @return int
     */
    protected function convertToBytes($value)
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);

        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * AJAX: Create a new API key.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function ajaxCreateApiKey()
    {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'codeia_create_api_key')) {
            wp_die(__('Security check failed.', 'wp-api-codeia'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to create API keys.', 'wp-api-codeia'));
        }

        // Generate API key
        $siteId = get_current_blog_id();
        $userId = get_current_user_id();
        $random = wp_generate_password(32, false);
        $checksum = hash('crc32', $siteId . $userId . $random);

        $apiKey = sprintf('wack_%d_%d_%s_%s', $siteId, $userId, $random, $checksum);

        // Save to database
        global $wpdb;
        $table = $wpdb->prefix . 'codeia_api_keys';

        $wpdb->insert(
            $table,
            array(
                'api_key' => $apiKey,
                'user_id' => $userId,
                'name' => sprintf(__('API Key for %s', 'wp-api-codeia'), wp_get_current_user()->display_name),
                'scopes' => 'read,write',
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%s', '%s', '%s')
        );

        // Redirect back to authentication page with success message
        wp_redirect(admin_url('admin.php?page=wp-api-codeia-auth&created=1'));
        exit;
    }

    /**
     * AJAX: Revoke an API key.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function ajaxRevokeApiKey()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'codeia_revoke_api_key')) {
            wp_send_json_error(__('Security check failed.', 'wp-api-codeia'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to revoke API keys.', 'wp-api-codeia'));
        }

        $keyId = isset($_POST['key_id']) ? (int) $_POST['key_id'] : 0;

        if (!$keyId) {
            wp_send_json_error(__('Invalid key ID.', 'wp-api-codeia'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'codeia_api_keys';

        $result = $wpdb->update(
            $table,
            array('is_revoked' => 1),
            array('api_key_id' => $keyId),
            array('%d'),
            array('%d')
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => __('API key revoked successfully.', 'wp-api-codeia')));
        } else {
            wp_send_json_error(__('Failed to revoke API key.', 'wp-api-codeia'));
        }
    }

    /**
     * AJAX: List API keys.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function ajaxListApiKeys()
    {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'codeia_list_api_keys')) {
            wp_send_json_error(__('Security check failed.', 'wp-api-codeia'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to list API keys.', 'wp-api-codeia'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'codeia_api_keys';

        $keys = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 50",
            get_current_user_id()
        ));

        wp_send_json_success(array('keys' => $keys));
    }

    /**
     * AJAX: Get fields for a post type.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function ajaxGetPostTypeFields()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'codeia_get_fields')) {
            wp_send_json_error(__('Security check failed.', 'wp-api-codeia'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission.', 'wp-api-codeia'));
        }

        $postType = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';

        if (!$postType) {
            wp_send_json_error(__('Invalid post type.', 'wp-api-codeia'));
        }

        // Get fields using the detector
        if ($this->container && $this->container->has('detector')) {
            $detector = $this->container->get('detector');
            $fieldDetector = $detector->getFieldDetector();

            if ($fieldDetector) {
                // Get all fields as a flat array
                $allFields = $fieldDetector->getAllFields($postType);
                // Extract just the field names (keys)
                $fieldNames = array_keys($allFields);

                wp_send_json_success(array('fields' => $fieldNames));
            }
        }

        // Fallback: get basic fields
        $fields = $this->getPostTypeFields($postType);
        wp_send_json_success(array('fields' => $fields));
    }

    /**
     * AJAX: Save field configuration for a post type.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function ajaxSaveFieldConfig()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'codeia_save_fields')) {
            wp_send_json_error(__('Security check failed.', 'wp-api-codeia'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission.', 'wp-api-codeia'));
        }

        $postType = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : '';
        $fields = isset($_POST['fields']) ? array_map('sanitize_text_field', (array) $_POST['fields']) : array();

        if (!$postType) {
            wp_send_json_error(__('Invalid post type.', 'wp-api-codeia'));
        }

        // Get existing config
        $fieldConfig = get_option('wp_api_codeia_fields', array());
        $fieldConfig[$postType] = $fields;

        // Save
        update_option('wp_api_codeia_fields', $fieldConfig);

        // Clear field detector cache to ensure fresh data
        if ($this->container && $this->container->has('detector')) {
            $detector = $this->container->get('detector');
            if (method_exists($detector, 'clearSchemaCache')) {
                $detector->clearSchemaCache();
            }
        }

        wp_send_json_success(array(
            'message' => __('Field configuration saved.', 'wp-api-codeia'),
            'saved' => $fields
        ));
    }

    /**
     * Get fields for a post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array
     */
    protected function getPostTypeFields($postType)
    {
        $fields = array('title', 'content', 'excerpt', 'status', 'author', 'date', 'slug');

        // Get meta fields
        $metaKeys = get_registered_meta_keys($postType);

        foreach ($metaKeys as $key => $args) {
            if (isset($args['show_in_rest']) && $args['show_in_rest']) {
                $fields[] = $key;
            }
        }

        return $fields;
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
        $customPath = locate_template('wp-api-codeia/admin/' . $template);

        if ($customPath) {
            return $customPath;
        }

        return WP_API_CODEIA_PLUGIN_DIR . '/templates/admin/' . $template;
    }

    /**
     * Shortcode: Embed Swagger UI documentation.
     *
     * @since 1.0.0
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function shortcodeApiDocs($atts)
    {
        $atts = shortcode_atts(array(
            'height' => '800px',
        ), $atts);

        $specUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs');

        ob_start();
        include WP_API_CODEIA_PLUGIN_DIR . '/templates/swagger.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Embed ReDoc documentation.
     *
     * @since 1.0.0
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function shortcodeApiRedoc($atts)
    {
        $atts = shortcode_atts(array(
            'height' => '800px',
        ), $atts);

        $specUrl = rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/docs');

        ob_start();
        include WP_API_CODEIA_PLUGIN_DIR . '/templates/redoc.php';
        return ob_get_clean();
    }
}
