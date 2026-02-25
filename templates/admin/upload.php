<?php
/**
 * Admin Upload Template
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Media Upload Settings', 'wp-api-codeia'); ?></h1>

    <div class="codeia-section-header">
        <h2><?php esc_html_e('Upload Configuration', 'wp-api-codeia'); ?></h2>
    </div>

    <table class="form-table">
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Maximum File Size', 'wp-api-codeia'); ?></label>
            </th>
            <td>
                <code><?php echo esc_html($maxFileSize['formatted']); ?></code>
                <p class="description">
                    <?php esc_html_e('The maximum size of files that can be uploaded through the API.', 'wp-api-codeia'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('Allowed MIME Types', 'wp-api-codeia'); ?></label>
            </th>
            <td>
                <ul style="max-height: 200px; overflow-y: auto; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
                    <?php foreach ($allowedMimes as $mime => $extension): ?>
                        <li><code><?php echo esc_html($mime); ?></code></li>
                    <?php endforeach; ?>
                </ul>
                <p class="description">
                    <?php esc_html_e('File types that are allowed to be uploaded through the API.', 'wp-api-codeia'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label><?php esc_html_e('API Endpoint', 'wp-api-codeia'); ?></label>
            </th>
            <td>
                <code>POST /<?php echo esc_html(WP_API_CODEIA_API_NAMESPACE); ?>/v1/media</code>
                <p class="description">
                    <?php esc_html_e('Use this endpoint to upload files through the API.', 'wp-api-codeia'); ?>
                </p>
            </td>
        </tr>
    </table>

    <div style="margin-top: 30px;">
        <h3><?php esc_html_e('Upload Example', 'wp-api-codeia'); ?></h3>
        <pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; overflow-x: auto;"><code>curl -X POST <?php echo rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/media'); ?> \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -F "file=@/path/to/image.jpg" \
  -F "alt_text=My image"</code></pre>
    </div>

    <div style="margin-top: 20px; padding: 15px; background: #fff; border-left: 4px solid #0073aa;">
        <p>
            <strong><?php esc_html_e('Note:', 'wp-api-codeia'); ?></strong>
            <?php esc_html_e('All uploads are validated for security, file size, and MIME type before being processed.', 'wp-api-codeia'); ?>
        </p>
    </div>
</div>
