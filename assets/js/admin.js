/**
 * WP API Codeia Admin Scripts
 */

(function($) {
    'use strict';

    // API Key Management (for authentication page)
    $(document).ready(function() {
        // Copy API key to clipboard
        $(document).on('click', '.codeia-copy-key', function(e) {
            e.preventDefault();
            const keyId = $(this).data('key-id');
            const $keyDisplay = $('#api-key-' + keyId);
            const key = $keyDisplay.text();

            if (navigator.clipboard) {
                navigator.clipboard.writeText(key).then(function() {
                    const $btn = $('.codeia-copy-key[data-key-id="' + keyId + '"]');
                    const originalText = $btn.text();
                    $btn.text(codeiaAdmin.strings.saved || 'Copied!');
                    setTimeout(function() {
                        $btn.text(originalText);
                    }, 2000);
                });
            } else {
                // Fallback for older browsers
                const $temp = $('<input>');
                $('body').append($temp);
                $temp.val(key).select();
                document.execCommand('copy');
                $temp.remove();
            }
        });

        // Revoke API key
        $(document).on('click', '.codeia-revoke-key', function(e) {
            e.preventDefault();
            const keyId = $(this).data('key-id');

            if (!confirm(codeiaAdmin.strings.confirm_revoke || 'Are you sure?')) {
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true).text('Revoking...');

            $.ajax({
                url: codeiaAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'codeia_revoke_api_key',
                    key_id: keyId,
                    nonce: codeiaAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#api-key-row-' + keyId).fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message || 'Failed to revoke key');
                        $btn.prop('disabled', false).text('Revoke');
                    }
                },
                error: function() {
                    alert(codeiaAdmin.strings.error || 'An error occurred');
                    $btn.prop('disabled', false).text('Revoke');
                }
            });
        });

        // Generate new API key
        $('#codeia-generate-key').on('click', function(e) {
            e.preventDefault();
            const $btn = $(this);
            const $nameInput = $('#api-key-name');
            const name = $nameInput.val().trim();

            if (!name) {
                alert('Please enter a name for the API key');
                return;
            }

            $btn.prop('disabled', true).text('Generating...');

            $.ajax({
                url: codeiaAdmin.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'codeia_create_api_key',
                    name: name,
                    nonce: codeiaAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Failed to generate key');
                        $btn.prop('disabled', false).text('Generate Key');
                    }
                },
                error: function() {
                    alert(codeiaAdmin.strings.error || 'An error occurred');
                    $btn.prop('disabled', false).text('Generate Key');
                }
            });
        });
    });

})(jQuery);
