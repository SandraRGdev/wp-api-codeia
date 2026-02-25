<?php
/**
 * Image Validator
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Upload\Validators;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image Validator.
 *
 * Validates image files.
 *
 * @since 1.0.0
 */
class ImageValidator implements UploadValidator
{
    /**
     * Validate image file.
     *
     * @since 1.0.0
     *
     * @param array $file    File data.
     * @param array $options Validation options.
     * @return true|\WP_Error
     */
    public function validate($file, $options = array())
    {
        // Only validate if it's an image
        if (strpos($file['type'], 'image/') !== 0) {
            return true;
        }

        // Check if GD or ImageMagick is available
        if (!wp_image_editor_supports(array('methods' => array('get_image')))) {
            return new \WP_Error(
                'no_image_support',
                __('Image processing is not available on this server', 'wp-api-codeia'),
                array('status' => 500)
            );
        }

        // Get image dimensions
        $imageInfo = getimagesize($file['tmp_name']);

        if ($imageInfo === false) {
            return new \WP_Error(
                'invalid_image',
                __('File is not a valid image', 'wp-api-codeia'),
                array('status' => 400)
            );
        }

        // Check minimum dimensions
        if (isset($options['min_width']) && $imageInfo[0] < $options['min_width']) {
            return new \WP_Error(
                'image_too_small',
                sprintf(__('Image width must be at least %d pixels', 'wp-api-codeia'), $options['min_width']),
                array('status' => 400)
            );
        }

        if (isset($options['min_height']) && $imageInfo[1] < $options['min_height']) {
            return new \WP_Error(
                'image_too_small',
                sprintf(__('Image height must be at least %d pixels', 'wp-api-codeia'), $options['min_height']),
                array('status' => 400)
            );
        }

        // Check maximum dimensions
        if (isset($options['max_width']) && $imageInfo[0] > $options['max_width']) {
            return new \WP_Error(
                'image_too_large',
                sprintf(__('Image width cannot exceed %d pixels', 'wp-api-codeia'), $options['max_width']),
                array('status' => 400)
            );
        }

        if (isset($options['max_height']) && $imageInfo[1] > $options['max_height']) {
            return new \WP_Error(
                'image_too_large',
                sprintf(__('Image height cannot exceed %d pixels', 'wp-api-codeia'), $options['max_height']),
                array('status' => 400)
            );
        }

        return true;
    }
}
