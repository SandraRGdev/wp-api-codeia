<?php
/**
 * Size Validator
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Upload\Validators;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Size Validator.
 *
 * Validates file size against maximum allowed size.
 *
 * @since 1.0.0
 */
class SizeValidator implements UploadValidator
{
    /**
     * Maximum file size in bytes.
     *
     * @since 1.0.0
     *
     * @var int
     */
    protected $maxSize;

    /**
     * Create a new Size Validator instance.
     *
     * @since 1.0.0
     *
     * @param int $maxSize Maximum file size in bytes.
     */
    public function __construct($maxSize)
    {
        $this->maxSize = $maxSize;
    }

    /**
     * Validate file size.
     *
     * @since 1.0.0
     *
     * @param array $file    File data.
     * @param array $options Validation options.
     * @return true|\WP_Error
     */
    public function validate($file, $options = array())
    {
        $maxSize = isset($options['max_file_size']) ? $options['max_file_size'] : $this->maxSize;

        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 2);
            $fileSizeMB = round($file['size'] / 1024 / 1024, 2);

            return new \WP_Error(
                'file_too_large',
                sprintf(
                    __('File exceeds maximum size of %s MB (uploaded file is %s MB)', 'wp-api-codeia'),
                    $maxSizeMB,
                    $fileSizeMB
                ),
                array('status' => 400)
            );
        }

        return true;
    }

    /**
     * Get maximum size.
     *
     * @since 1.0.0
     *
     * @return int
     */
    public function getMaxSize()
    {
        return $this->maxSize;
    }

    /**
     * Set maximum size.
     *
     * @since 1.0.0
     *
     * @param int $maxSize Maximum size in bytes.
     * @return void
     */
    public function setMaxSize($maxSize)
    {
        $this->maxSize = $maxSize;
    }
}
