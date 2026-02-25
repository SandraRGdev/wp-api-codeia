<?php
/**
 * Admin Authentication Template
 *
 * @package WP_API_Codeia
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get API keys for current user
global $wpdb;
$keysTable = $wpdb->prefix . 'codeia_api_keys';
$userKeys = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$keysTable} WHERE user_id = %d AND is_revoked = 0 ORDER BY created_at DESC LIMIT 20",
    get_current_user_id()
));
?>

<div class="wrap">
    <h1><?php esc_html_e('Authentication', 'wp-api-codeia'); ?></h1>

    <?php if (isset($_GET['created']) && $_GET['created'] == '1'): ?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e('API key created successfully! See below for your new key.', 'wp-api-codeia'); ?></p>
    </div>
    <?php endif; ?>

    <div class="codeia-section-header" style="margin-top: 20px;">
        <h2><?php esc_html_e('Your API Keys', 'wp-api-codeia'); ?></h2>
    </div>

    <p>
        <?php esc_html_e('API keys allow you to authenticate requests to the API. Include your key in the X-API-Key header.', 'wp-api-codeia'); ?>
    </p>

    <p>
        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-ajax.php?action=codeia_create_api_key'), 'codeia_create_api_key', 'nonce')); ?>"
           class="button button-primary">
            <?php esc_html_e('Generate New API Key', 'wp-api-codeia'); ?>
        </a>
    </p>

    <?php if (!empty($userKeys)): ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Name', 'wp-api-codeia'); ?></th>
                <th><?php esc_html_e('API Key', 'wp-api-codeia'); ?></th>
                <th><?php esc_html_e('Created', 'wp-api-codeia'); ?></th>
                <th><?php esc_html_e('Last Used', 'wp-api-codeia'); ?></th>
                <th><?php esc_html_e('Actions', 'wp-api-codeia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($userKeys as $key): ?>
            <tr>
                <td><?php echo esc_html($key->name); ?></td>
                <td>
                    <code style="font-size: 12px; background: #f0f0f0; padding: 4px 8px; border-radius: 3px;">
                        <?php echo esc_html(substr($key->api_key, 0, 20)); ?>...
                    </code>
                    <button type="button" class="button button-small" onclick="codeiaCopyKey('<?php echo esc_js($key->api_key); ?>')">
                        <?php esc_html_e('Copy', 'wp-api-codeia'); ?>
                    </button>
                </td>
                <td><?php echo esc_html(mysql2date('Y-m-d H:i', $key->created_at)); ?></td>
                <td><?php echo $key->last_used ? esc_html(mysql2date('Y-m-d H:i', $key->last_used)) : '-'; ?></td>
                <td>
                    <button type="button" class="button button-small button-link-delete" onclick="codeiaRevokeKey(<?php echo (int) $key->api_key_id; ?>)">
                        <?php esc_html_e('Revoke', 'wp-api-codeia'); ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p><?php esc_html_e('No API keys found. Generate one to start using the API.', 'wp-api-codeia'); ?></p>
    <?php endif; ?>

    <hr style="margin: 30px 0;">

    <div class="codeia-section-header">
        <h2><?php esc_html_e('How to Use Your API Key', 'wp-api-codeia'); ?></h2>
    </div>

    <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-top: 15px;">
        <h3><?php esc_html_e('Example Request:', 'wp-api-codeia'); ?></h3>
        <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;"><code>curl -X GET "<?php echo rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/posts'); ?>" \
  -H "X-API-Key: YOUR_API_KEY_HERE"</code></pre>
    </div>

    <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-top: 15px;">
        <h3><?php esc_html_e('JavaScript Example:', 'wp-api-codeia'); ?></h3>
        <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;"><code>fetch('<?php echo rest_url(WP_API_CODEIA_API_NAMESPACE . '/v1/posts'); ?>', {
  headers: {
    'X-API-Key': 'YOUR_API_KEY_HERE'
  }
})
.then(response => response.json())
.then(data => console.log(data));</code></pre>
    </div>
</div>

<script>
function codeiaCopyKey(apiKey) {
    navigator.clipboard.writeText(apiKey).then(function() {
        alert('<?php esc_html_e('API Key copied to clipboard!', 'wp-api-codeia'); ?>');
    });
}

function codeiaRevokeKey(keyId) {
    if (!confirm('<?php esc_html_e('Are you sure you want to revoke this API key? This action cannot be undone.', 'wp-api-codeia'); ?>')) {
        return;
    }

    var nonce = '<?php echo wp_create_nonce('codeia_revoke_api_key'); ?>';

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=codeia_revoke_api_key&nonce=' + encodeURIComponent(nonce) + '&key_id=' + keyId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.data.message || '<?php esc_html_e('Failed to revoke API key.', 'wp-api-codeia'); ?>');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('<?php esc_html_e('An error occurred.', 'wp-api-codeia'); ?>');
    });
}
</script>
