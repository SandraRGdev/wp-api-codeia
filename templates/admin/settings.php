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

// Get current configuration
$enabledPostTypes = get_option('wp_api_codeia_enabled_post_types', array('post', 'page'));
$authConfig = get_option('wp_api_codeia_auth_config', array());
$rateLimitConfig = get_option('wp_api_codeia_rate_limit', array());
$fieldConfig = get_option('wp_api_codeia_fields', array());

// Get all registered post types
$allPostTypes = get_post_types(array('show_in_rest' => true), 'objects');
?>

<div class="wrap">
    <h1><?php esc_html_e('API Settings', 'wp-api-codeia'); ?></h1>

    <form method="post" action="options.php" id="codeia-settings-form">
        <?php settings_fields('wp-api-codeia'); ?>
        <?php do_settings_sections('wp-api-codeia'); ?>

        <h2><?php esc_html_e('Enabled Post Types', 'wp-api-codeia'); ?></h2>
        <table class="form-table">
            <?php foreach ($allPostTypes as $postType): ?>
            <tr>
                <th scope="row">
                    <label for="pt_<?php echo esc_attr($postType->name); ?>">
                        <?php echo esc_html($postType->labels->name); ?>
                        <code style="display:block; margin-top:5px; font-size:11px; color:#666;">
                            <?php echo esc_html($postType->name); ?>
                        </code>
                    </label>
                </th>
                <td>
                    <input type="checkbox"
                           name="wp_api_codeia_enabled_post_types[]"
                           id="pt_<?php echo esc_attr($postType->name); ?>"
                           value="<?php echo esc_attr($postType->name); ?>"
                           <?php checked(in_array($postType->name, $enabledPostTypes)); ?>>
                    <label for="pt_<?php echo esc_attr($postType->name); ?>">
                        <?php esc_html_e('Enable API endpoints', 'wp-api-codeia'); ?>
                    </label>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <hr style="margin: 30px 0;">

        <h2><?php esc_html_e('Authentication Settings', 'wp-api-codeia'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="default_auth"><?php esc_html_e('Default Authentication', 'wp-api-codeia'); ?></label>
                </th>
                <td>
                    <select name="wp_api_codeia_auth_config[default]" id="default_auth">
                        <option value="public" <?php selected(isset($authConfig['default']) && $authConfig['default'] === 'public'); ?>>
                            <?php esc_html_e('Public (no authentication)', 'wp-api-codeia'); ?>
                        </option>
                        <option value="api_key" <?php selected(isset($authConfig['default']) && $authConfig['default'] === 'api_key'); ?>>
                            <?php esc_html_e('API Key', 'wp-api-codeia'); ?>
                        </option>
                        <option value="jwt" <?php selected(isset($authConfig['default']) && $authConfig['default'] === 'jwt'); ?>>
                            <?php esc_html_e('JWT Token', 'wp-api-codeia'); ?>
                        </option>
                        <option value="any" <?php selected(isset($authConfig['default']) && $authConfig['default'] === 'any'); ?>>
                            <?php esc_html_e('Any valid method', 'wp-api-codeia'); ?>
                        </option>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Default authentication method for all endpoints. Can be overridden per endpoint.', 'wp-api-codeia'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('JWT Settings', 'wp-api-codeia'); ?></label>
                </th>
                <td>
                    <label>
                        <?php esc_html_e('Access Token TTL (seconds)', 'wp-api-codeia'); ?>
                        <input type="number" name="wp_api_codeia_auth_config[jwt_access_ttl]"
                               value="<?php echo esc_attr($authConfig['jwt_access_ttl'] ?? 3600); ?>"
                               class="small-text">
                    </label>
                    <br>
                    <label>
                        <?php esc_html_e('Refresh Token TTL (seconds)', 'wp-api-codeia'); ?>
                        <input type="number" name="wp_api_codeia_auth_config[jwt_refresh_ttl]"
                               value="<?php echo esc_attr($authConfig['jwt_refresh_ttl'] ?? 2592000); ?>"
                               class="small-text">
                    </label>
                </td>
            </tr>
        </table>

        <hr style="margin: 30px 0;">

        <h2><?php esc_html_e('Rate Limiting', 'wp-api-codeia'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="rate_limit_enabled"><?php esc_html_e('Enable Rate Limiting', 'wp-api-codeia'); ?></label>
                </th>
                <td>
                    <input type="hidden" name="wp_api_codeia_rate_limit[enabled]" value="0">
                    <input type="checkbox" name="wp_api_codeia_rate_limit[enabled]" id="rate_limit_enabled"
                           value="1" <?php checked(!empty($rateLimitConfig['enabled'])); ?>>
                    <label for="rate_limit_enabled">
                        <?php esc_html_e('Enable rate limiting for API requests', 'wp-api-codeia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="rate_limit_requests"><?php esc_html_e('Requests per Hour', 'wp-api-codeia'); ?></label>
                </th>
                <td>
                    <input type="number" name="wp_api_codeia_rate_limit[requests_per_hour]" id="rate_limit_requests"
                           value="<?php echo esc_attr($rateLimitConfig['requests_per_hour'] ?? 1000); ?>"
                           class="small-text">
                    <p class="description">
                        <?php esc_html_e('Maximum requests per hour per user/IP.', 'wp-api-codeia'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Settings', 'wp-api-codeia')); ?>
    </form>

    <hr style="margin: 30px 0;">

    <h2><?php esc_html_e('Field Configuration', 'wp-api-codeia'); ?></h2>
    <p><?php esc_html_e('Configure which fields are exposed for each post type. Click on a post type to configure its fields.', 'wp-api-codeia'); ?></p>

    <div id="field-config-container" data-loading="false">
        <select id="post-type-selector" class="regular-text">
            <option value=""><?php esc_html_e('Select a post type...', 'wp-api-codeia'); ?></option>
            <?php foreach ($allPostTypes as $pt): ?>
            <option value="<?php echo esc_attr($pt->name); ?>">
                <?php echo esc_html($pt->labels->name); ?> (<?php echo esc_html($pt->name); ?>)
            </option>
            <?php endforeach; ?>
        </select>

        <div id="fields-config-area" style="display:none; margin-top: 20px;">
            <div class="fields-loading" style="display:none;">
                <span class="spinner is-active"></span>
                <?php esc_html_e('Loading fields...', 'wp-api-codeia'); ?>
            </div>
            <div id="fields-list"></div>
        </div>
    </div>
</div>

<style>
.codeia-field-item {
    padding: 10px;
    border: 1px solid #ddd;
    margin-bottom: 5px;
    background: #f9f9f9;
}
.codeia-field-item label {
    font-weight: 600;
}
.codeia-field-item input[type="checkbox"] {
    margin-right: 10px;
}
.codeia-message {
    margin: 10px 0;
}
.codeia-message p {
    margin: 0.5em 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    var fieldConfig = <?php echo json_encode($fieldConfig); ?>;
    var currentPostType = '';

    // Store nonces in variables for cleaner code
    var getFieldsNonce = '<?php echo wp_create_nonce('codeia_get_fields'); ?>';
    var saveFieldsNonce = '<?php echo wp_create_nonce('codeia_save_fields'); ?>';

    // Restore last selected post type from localStorage
    var lastSelectedPostType = localStorage.getItem('codeia_selected_post_type');
    if (lastSelectedPostType) {
        $('#post-type-selector').val(lastSelectedPostType);
        // Trigger change to load fields
        setTimeout(function() {
            $('#post-type-selector').trigger('change');
        }, 100);
    }

    $('#post-type-selector').on('change', function() {
        currentPostType = $(this).val();
        if (!currentPostType) return;

        // Save selection to localStorage
        localStorage.setItem('codeia_selected_post_type', currentPostType);

        $('#fields-config-area').show();
        $('.fields-loading').show();
        $('#fields-list').empty();

        // Fetch schema for this post type
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'codeia_get_post_type_fields',
                post_type: currentPostType,
                nonce: getFieldsNonce
            },
            success: function(response) {
                console.log('Fields response:', response);
                $('.fields-loading').hide();
                if (response.success && response.data.fields) {
                    renderFieldsConfig(currentPostType, response.data.fields);
                } else if (!response.success) {
                    $('#fields-list').html('<p class="error">Error: ' + (response.data || 'Unknown') + '</p>');
                }
            },
            error: function(xhr, status, error) {
                $('.fields-loading').hide();
                $('#fields-list').html('<p class="error">Failed to load fields: ' + xhr.status + ' ' + error + '</p>');
            }
        });
    });

    // Use event delegation for save button (prevents multiple handlers)
    $(document).on('click', '#save-fields-config', function() {
        if (!currentPostType) return;

        var selectedFields = [];
        $('#fields-list input[type="checkbox"]:checked').each(function() {
            var name = $(this).attr('name');
            if (name && name.indexOf('fields[') === 0) {
                selectedFields.push(name.replace('fields[', '').replace(']', ''));
            }
        });

        var $btn = $(this);
        $btn.prop('disabled', true).text('Saving...');

        // Remove any existing messages
        $('#fields-list .codeia-message').remove();

        // Add loading message
        $('#fields-list').append('<div class="codeia-message notice notice-info inline"><p>Saving configuration...</p></div>');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'codeia_save_field_config',
                post_type: currentPostType,
                fields: selectedFields,
                nonce: saveFieldsNonce
            },
            success: function(response) {
                $('#fields-list .codeia-message').remove();

                if (response.success) {
                    // Update local config
                    fieldConfig[currentPostType] = selectedFields;

                    // Add success message
                    $('#fields-list').append('<div class="codeia-message notice notice-success inline"><p>Configuration saved! ' + selectedFields.length + ' fields configured for ' + currentPostType + '.</p></div>');

                    // Update summary text
                    $('#fields-list p strong').first().text(selectedFields.length + ' fields configured');
                } else {
                    $('#fields-list').append('<div class="codeia-message notice notice-error inline"><p>Error: ' + (response.data.message || response.data || 'Failed to save configuration') + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                $('#fields-list .codeia-message').remove();
                $('#fields-list').append('<div class="codeia-message notice notice-error inline"><p>An error occurred while saving: ' + xhr.status + ' ' + error + '</p></div>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Save Field Configuration');
            }
        });
    });

    function renderFieldsConfig(postType, fields) {
        var enabledFields = fieldConfig[postType] || [];

        // Convert to real array and ensure proper comparison
        if (!Array.isArray(enabledFields)) {
            enabledFields = Object.values(enabledFields || []);
        }
        // Make sure we have a clean array of strings
        enabledFields = Array.from(enabledFields).map(String).filter(function(v) { return v !== ''; });

        // Clear the fields list first
        $('#fields-list').empty().append('<h3>' + $('#post-type-selector option:selected').text() + '</h3>');

        // Native fields
        var nativeFields = ['title', 'content', 'excerpt', 'status', 'author', 'date', 'slug'];
        $('#fields-list').append('<h4>Native Fields</h4>');

        nativeFields.forEach(function(field) {
            var isChecked = enabledFields.indexOf(field) !== -1 || enabledFields.indexOf('*') !== -1;
            // Create checkbox and add to DOM first
            var $div = $('<div class="codeia-field-item"><label><input type="checkbox" name="fields[' + field + ']"> ' + field + '</label></div>');
            $('#fields-list').append($div);
            // Then find the checkbox and set both the attribute AND the property
            var $checkbox = $div.find('input[type="checkbox"]');
            if (isChecked) {
                $checkbox.attr('checked', 'checked').prop('checked', true);
            }
        });

        // Custom/meta fields - combine server fields with enabled fields to show all
        var metaFields = fields.filter(function(f) {
            return nativeFields.indexOf(f) === -1;
        });

        // Also add any enabled fields that might not be in the current server response
        enabledFields.forEach(function(field) {
            if (nativeFields.indexOf(field) === -1 && metaFields.indexOf(field) === -1) {
                metaFields.push(field);
            }
        });

        // Remove duplicates
        metaFields = [...new Set(metaFields)];

        if (metaFields.length > 0) {
            $('#fields-list').append('<h4>Custom Fields</h4>');
            metaFields.forEach(function(field) {
                var isChecked = enabledFields.indexOf(field) !== -1 || enabledFields.indexOf('*') !== -1;
                // Create checkbox and add to DOM first
                var $div = $('<div class="codeia-field-item"><label><input type="checkbox" name="fields[' + field + ']"> ' + field + '</label></div>');
                $('#fields-list').append($div);
                // Then find the checkbox and set both the attribute AND the property
                var $checkbox = $div.find('input[type="checkbox"]');
                if (isChecked) {
                    $checkbox.attr('checked', 'checked').prop('checked', true);
                }
            });
        }

        // Save button with summary
        var selectedCount = enabledFields.length;
        var summaryHtml = '<p><strong>' + selectedCount + ' fields configured</strong></p>';
        $('#fields-list').append(summaryHtml);

        $('#fields-list').append('<p><button type="button" class="button button-primary" id="save-fields-config">Save Field Configuration</button></p>');

        // Update summary when checkboxes change
        $('#fields-list input[type="checkbox"]').on('change', function() {
            var count = $('#fields-list input[type="checkbox"]:checked').length;
            $('#fields-list p strong').first().text(count + ' fields selected');
        });
    }
});
</script>
