<?php
/**
 * Extension Validator
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Upload\Validators;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Extension Validator.
 *
 * Validates file extension against allowed extensions.
 *
 * @since 1.0.0
 */
class ExtensionValidator implements UploadValidator
{
    /**
     * Validate file extension.
     *
     * @since 1.0.0
     *
     * @param array $file    File data.
     * @param array $options Validation options.
     * @return true|\WP_Error
     */
    public function validate($file, $options = array())
    {
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (empty($extension)) {
            return new \WP_Error(
                'no_extension',
                __('File has no extension', 'wp-api-codeia'),
                array('status' => 400)
            );
        }

        // Check against explicitly allowed extensions
        if (!empty($options['allowed_extensions'])) {
            $allowedExtensions = array_map('strtolower', $options['allowed_extensions']);

            if (!in_array($extension, $allowedExtensions, true)) {
                return new \WP_Error(
                    'invalid_extension',
                    sprintf(__('File extension .%s is not allowed', 'wp-api-codeia'), $extension),
                    array('status' => 400)
                );
            }
        }

        // Check against dangerous extensions
        $dangerous = array('php', 'php5', 'php7', 'phtml', 'exe', 'sh', 'js', 'jar');

        if (in_array($extension, $dangerous, true)) {
            return new \WP_Error(
                'dangerous_extension',
                sprintf(__('File extension .%s is not allowed for security reasons', 'wp-api-codeia'), $extension),
                array('status' => 400)
            );
        }

        return true;
    }
}
