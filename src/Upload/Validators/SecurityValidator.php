<?php
/**
 * Security Validator
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Upload\Validators;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Validator.
 *
 * Performs security checks on uploaded files.
 *
 * @since 1.0.0
 */
class SecurityValidator implements UploadValidator
{
    /**
     * Validate file security.
     *
     * @since 1.0.0
     *
     * @param array $file    File data.
     * @param array $options Validation options.
     * @return true|\WP_Error
     */
    public function validate($file, $options = array())
    {
        // Check for file contents
        if (!$this->validateFileContents($file['tmp_name'])) {
            return new \WP_Error(
                'malicious_file',
                __('File contains malicious content', 'wp-api-codeia'),
                array('status' => 400)
            );
        }

        // Validate filename
        if (!$this->validateFilename($file['name'])) {
            return new \WP_Error(
                'invalid_filename',
                __('Filename contains invalid characters', 'wp-api-codeia'),
                array('status' => 400)
            );
        }

        return true;
    }

    /**
     * Validate file contents.
     *
     * @since 1.0.0
     *
     * @param string $filepath File path.
     * @return bool
     */
    protected function validateFileContents($filepath)
    {
        $handle = fopen($filepath, 'rb');

        if (!$handle) {
            return false;
        }

        $content = fread($handle, 1024);
        fclose($handle);

        // Check for PHP tags
        if (strpos($content, '<?php') !== false) {
            return false;
        }

        // Check for suspicious patterns
        $suspicious = array(
            '<script',
            'javascript:',
            'eval(',
            'base64_decode',
            'system(',
            'exec(',
            'shell_exec(',
            'passthru(',
        );

        foreach ($suspicious as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate filename.
     *
     * @since 1.0.0
     *
     * @param string $filename Filename.
     * @return bool
     */
    protected function validateFilename($filename)
    {
        // Check for invalid characters
        if (preg_match('/[^\w\s\-\.~]/', $filename)) {
            return false;
        }

        // Check for path traversal
        if (strpos($filename, '..') !== false) {
            return false;
        }

        // Check for null bytes
        if (strpos($filename, "\0") !== false) {
            return false;
        }

        return true;
    }
}
