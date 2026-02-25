<?php
/**
 * Admin Permissions Template
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('API Permissions', 'wp-api-codeia'); ?></h1>

    <div class="codeia-section-header">
        <h2><?php esc_html_e('Role-Based Permissions', 'wp-api-codeia'); ?></h2>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Role', 'wp-api-codeia'); ?></th>
                <th><?php esc_html_e('Read', 'wp-api-codeia'); ?></th>
                <th><?php esc_html_e('Create', 'wp-api-codeia'); ?></th>
                <th><?php esc_html_e('Update', 'wp-api-codeia'); ?></th>
                <th><?php esc_html_e('Delete', 'wp-api-codeia'); ?></th>
                <th><?php esc_html_e('Publish', 'wp-api-codeia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role_key => $role): ?>
            <tr>
                <td><strong><?php echo esc_html($role['name']); ?></strong></td>
                <td>
                    <?php $has_read = isset($matrix[$role_key]['read']) ? $matrix[$role_key]['read'] : false; ?>
                    <span class="codeia-badge <?php echo $has_read ? 'codeia-badge-success' : 'codeia-badge-warning'; ?>">
                        <?php echo $has_read ? __('Yes', 'wp-api-codeia') : __('No', 'wp-api-codeia'); ?>
                    </span>
                </td>
                <td>
                    <?php $has_create = isset($matrix[$role_key]['create']) ? $matrix[$role_key]['create'] : false; ?>
                    <span class="codeia-badge <?php echo $has_create ? 'codeia-badge-success' : 'codeia-badge-warning'; ?>">
                        <?php echo $has_create ? __('Yes', 'wp-api-codeia') : __('No', 'wp-api-codeia'); ?>
                    </span>
                </td>
                <td>
                    <?php $has_update = isset($matrix[$role_key]['update']) ? $matrix[$role_key]['update'] : false; ?>
                    <span class="codeia-badge <?php echo $has_update ? 'codeia-badge-success' : 'codeia-badge-warning'; ?>">
                        <?php echo $has_update ? __('Yes', 'wp-api-codeia') : __('No', 'wp-api-codeia'); ?>
                    </span>
                </td>
                <td>
                    <?php $has_delete = isset($matrix[$role_key]['delete']) ? $matrix[$role_key]['delete'] : false; ?>
                    <span class="codeia-badge <?php echo $has_delete ? 'codeia-badge-success' : 'codeia-badge-warning'; ?>">
                        <?php echo $has_delete ? __('Yes', 'wp-api-codeia') : __('No', 'wp-api-codeia'); ?>
                    </span>
                </td>
                <td>
                    <?php $has_publish = isset($matrix[$role_key]['publish']) ? $matrix[$role_key]['publish'] : false; ?>
                    <span class="codeia-badge <?php echo $has_publish ? 'codeia-badge-success' : 'codeia-badge-warning'; ?>">
                        <?php echo $has_publish ? __('Yes', 'wp-api-codeia') : __('No', 'wp-api-codeia'); ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top: 20px; padding: 15px; background: #fff; border-left: 4px solid #0073aa;">
        <p><?php esc_html_e('Permission configuration can be customized through the settings or by using filters.', 'wp-api-codeia'); ?></p>
    </div>
</div>
