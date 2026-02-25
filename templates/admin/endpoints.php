<?php
/**
 * Admin Endpoints Template
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(__('API Endpoints', 'wp-api-codeia')); ?></h1>

    <div class="codeia-section-header">
        <h2><?php echo esc_html(__('Post Types', 'wp-api-codeia')); ?></h2>
    </div>

    <table class="codeia-table wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html(__('Post Type', 'wp-api-codeia')); ?></th>
                <th><?php echo esc_html(__('Label', 'wp-api-codeia')); ?></th>
                <th><?php echo esc_html(__('REST Base', 'wp-api-codeia')); ?></th>
                <th><?php echo esc_html(__('Public', 'wp-api-codeia')); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($postTypes as $slug => $info): ?>
            <?php
            $restBase = isset($info['rest_base']) ? $info['rest_base'] : $slug;
            $baseUrl = '/v1/' . $restBase;
            ?>
            <tr>
                <td><code><?php echo esc_html($slug); ?></code></td>
                <td><?php echo esc_html($info['label']); ?></td>
                <td><code><?php echo esc_html($baseUrl); ?></code></td>
                <td>
                    <?php if (isset($info['api_visible']) && $info['api_visible']): ?>
                        <span class="codeia-badge codeia-badge-success"><?php echo esc_html(__('Yes', 'wp-api-codeia')); ?></span>
                    <?php else: ?>
                        <span class="codeia-badge codeia-badge-warning"><?php echo esc_html(__('No', 'wp-api-codeia')); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="codeia-section-header">
        <h2><?php echo esc_html(__('Taxonomies', 'wp-api-codeia')); ?></h2>
    </div>

    <table class="codeia-table wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html(__('Taxonomy', 'wp-api-codeia')); ?></th>
                <th><?php echo esc_html(__('Label', 'wp-api-codeia')); ?></th>
                <th><?php echo esc_html(__('REST Base', 'wp-api-codeia')); ?></th>
                <th><?php echo esc_html(__('Hierarchical', 'wp-api-codeia')); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($taxonomies as $slug => $info): ?>
            <?php
            $restBase = isset($info['rest_base']) ? $info['rest_base'] : $slug;
            $baseUrl = '/v1/' . $restBase;
            ?>
            <tr>
                <td><code><?php echo esc_html($slug); ?></code></td>
                <td><?php echo esc_html($info['label']); ?></td>
                <td><code><?php echo esc_html($baseUrl); ?></code></td>
                <td><?php echo $info['hierarchical'] ? __('Yes', 'wp-api-codeia') : __('No', 'wp-api-codeia'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
