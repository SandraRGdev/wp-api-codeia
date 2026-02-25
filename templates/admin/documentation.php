<?php
/**
 * Admin Documentation Template
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e('API Documentation', 'wp-api-codeia'); ?></h1>
    <p><?php esc_html_e('View and interact with the API documentation.', 'wp-api-codeia'); ?></p>

    <h2><?php esc_html_e('Documentation Links', 'wp-api-codeia'); ?></h2>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Format', 'wp-api-codeia'); ?></th>
                <th><?php esc_html_e('URL', 'wp-api-codeia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>OpenAPI JSON</strong></td>
                <td><a href="<?php echo esc_url($specUrl); ?>" target="_blank"><?php echo esc_html($specUrl); ?></a></td>
            </tr>
            <tr>
                <td><strong>Swagger UI</strong></td>
                <td><a href="<?php echo esc_url($swaggerUrl); ?>" target="_blank"><?php echo esc_html($swaggerUrl); ?></a></td>
            </tr>
            <tr>
                <td><strong>ReDoc</strong></td>
                <td><a href="<?php echo esc_url($redocUrl); ?>" target="_blank"><?php echo esc_html($redocUrl); ?></a></td>
            </tr>
        </tbody>
    </table>

    <h2><?php esc_html_e('Shortcodes', 'wp-api-codeia'); ?></h2>
    <p><code>[codeia_api_docs]</code> - <?php esc_html_e('Embed Swagger UI', 'wp-api-codeia'); ?></p>
    <p><code>[codeia_api_redoc]</code> - <?php esc_html_e('Embed ReDoc', 'wp-api-codeia'); ?></p>
</div>
