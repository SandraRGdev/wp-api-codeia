<?php
/**
 * Admin Settings Template
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(__('WP API Codeia Settings', 'wp-api-codeia')); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('wp-api-codeia'); ?>
        <?php do_settings_sections('wp-api-codeia'); ?>

        <?php submit_button(); ?>
    </form>
</div>
