<?php
/**
 * Admin Dashboard Template
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('WP API Codeia Dashboard', 'wp-api-codeia'); ?></h1>

    <div class="codeia-dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
        <div class="codeia-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0; font-size: 14px; color: #646970;">
                <?php esc_html_e('Active Tokens', 'wp-api-codeia'); ?>
            </h2>
            <p style="font-size: 32px; font-weight: 600; margin: 10px 0;">
                <?php echo esc_html($stats['active_tokens'] ?? 0); ?>
            </p>
        </div>

        <div class="codeia-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0; font-size: 14px; color: #646970;">
                <?php esc_html_e('Active API Keys', 'wp-api-codeia'); ?>
            </h2>
            <p style="font-size: 32px; font-weight: 600; margin: 10px 0;">
                <?php echo esc_html($stats['active_api_keys'] ?? 0); ?>
            </p>
        </div>

        <div class="codeia-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0; font-size: 14px; color: #646970;">
                <?php esc_html_e('Requests Today', 'wp-api-codeia'); ?>
            </h2>
            <p style="font-size: 32px; font-weight: 600; margin: 10px 0;">
                <?php echo esc_html($stats['total_requests_today'] ?? 0); ?>
            </p>
        </div>

        <div class="codeia-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <h2 style="margin-top: 0; font-size: 14px; color: #646970;">
                <?php esc_html_e('Cache Hits', 'wp-api-codeia'); ?>
            </h2>
            <p style="font-size: 32px; font-weight: 600; margin: 10px 0;">
                <?php echo esc_html($stats['cache_hits'] ?? 0); ?>
            </p>
        </div>
    </div>

    <div style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #ccd0d4;">
        <h2><?php esc_html_e('Quick Links', 'wp-api-codeia'); ?></h2>
        <ul>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=wp-api-codeia-auth')); ?>"><?php esc_html_e('Authentication Settings', 'wp-api-codeia'); ?></a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=wp-api-codeia-endpoints')); ?>"><?php esc_html_e('API Endpoints', 'wp-api-codeia'); ?></a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=wp-api-codeia-permissions')); ?>"><?php esc_html_e('Permissions', 'wp-api-codeia'); ?></a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=wp-api-codeia-docs')); ?>"><?php esc_html_e('Documentation', 'wp-api-codeia'); ?></a></li>
            <li><a href="<?php echo esc_url(rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/status')); ?>" target="_blank"><?php esc_html_e('API Status', 'wp-api-codeia'); ?></a></li>
        </ul>
    </div>
</div>
