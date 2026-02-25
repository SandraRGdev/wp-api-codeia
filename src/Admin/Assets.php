<?php
/**
 * Admin Assets
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Admin;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Assets.
 *
 * Handles admin CSS and JavaScript.
 *
 * @since 1.0.0
 */
class Assets
{
    /**
     * Enqueue admin styles.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function enqueueStyles()
    {
        wp_enqueue_style(
            'wp-api-codeia-admin',
            plugins_url('assets/css/admin.css', WP_API_CODEIA_PLUGIN_FILE),
            array(),
            WP_API_CODEIA_VERSION
        );
    }

    /**
     * Enqueue admin scripts.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function enqueueScripts()
    {
        wp_enqueue_script(
            'wp-api-codeia-admin',
            plugins_url('assets/js/admin.js', WP_API_CODEIA_PLUGIN_FILE),
            array('jquery', 'wp-color-picker'),
            WP_API_CODEIA_VERSION,
            true
        );

        wp_localize_script('wp-api-codeia-admin', 'codeiaAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('codeia_admin'),
            'restUrl' => rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'wp-api-codeia'),
                'confirm_revoke' => __('Are you sure you want to revoke this key? This action cannot be undone.', 'wp-api-codeia'),
                'saving' => __('Saving...', 'wp-api-codeia'),
                'saved' => __('Saved successfully', 'wp-api-codeia'),
                'error' => __('An error occurred. Please try again.', 'wp-api-codeia'),
            ),
        ));
    }

    /**
     * Print admin styles inline.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public static function printInlineStyles()
    {
        ?>
        <style>
            .codeia-dashboard { margin: 20px; }
            .codeia-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0; }
            .codeia-stat-card { background: #fff; border: 1px solid #c3c4c7; padding: 20px; border-radius: 4px; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
            .codeia-stat-label { color: #646970; font-size: 13px; margin-bottom: 5px; }
            .codeia-stat-value { font-size: 28px; font-weight: 600; color: #1d2327; }
            .codeia-section-header { display: flex; justify-content: space-between; align-items: center; margin: 30px 0 20px; }
            .codeia-section-header h2 { margin: 0; font-size: 20px; }
            .codeia-table { border-collapse: collapse; width: 100%; }
            .codeia-table th, .codeia-table td { padding: 12px; text-align: left; border-bottom: 1px solid #c3c4c7; }
            .codeia-table th { font-weight: 600; }
            .codeia-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
            .codeia-badge-success { background: #d7eddb; color: #155724; }
            .codeia-badge-warning { background: #fff3cd; color: #856404; }
            .codeia-badge-error { background: #f8d7da; color: #721c24; }
            .codeia-badge-info { background: #d1ecf1; color: #0c5460; }
            @media (max-width: 1200px) {
                .codeia-stats-grid { grid-template-columns: repeat(2, 1fr); }
            }
            @media (max-width: 600px) {
                .codeia-stats-grid { grid-template-columns: 1fr; }
            }
        </style>
        <?php
    }
}
