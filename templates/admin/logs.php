<?php
/**
 * Admin Logs Template
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(__('API Logs', 'wp-api-codeia')); ?></h1>

    <div class="codeia-section-header">
        <h2><?php echo esc_html(__('Recent Activity', 'wp-api-codeia')); ?></h2>
        <button type="button" class="button" id="codeia-refresh-logs">
            <?php echo esc_html(__('Refresh', 'wp-api-codeia')); ?>
        </button>
    </div>

    <table class="codeia-table wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php echo esc_html(__('Time', 'wp-api-codeia')); ?></th>
                <th><?php echo esc_html(__('Level', 'wp-api-codeia')); ?></th>
                <th><?php echo esc_html(__('Message', 'wp-api-codeia')); ?></th>
                <th><?php echo esc_html(__('Context', 'wp-api-codeia')); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
            <tr>
                <td colspan="4"><?php echo esc_html(__('No logs found. Enable debug logging to see activity.', 'wp-api-codeia')); ?></td>
            </tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo esc_html($log['time']); ?></td>
                    <td>
                        <?php
                        $level = $log['level'];
                        $badgeClass = 'codeia-badge-info';
                        if ($level === 'error' || $level === 'critical') {
                            $badgeClass = 'codeia-badge-error';
                        } elseif ($level === 'warning') {
                            $badgeClass = 'codeia-badge-warning';
                        }
                        ?>
                        <span class="codeia-badge <?php echo esc_attr($badgeClass); ?>"><?php echo esc_html($level); ?></span>
                    </td>
                    <td><?php echo esc_html($log['message']); ?></td>
                    <td><code><?php echo esc_html(json_encode($log['context'])); ?></code></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
