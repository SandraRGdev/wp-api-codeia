<?php
/**
 * MIME Validator
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Upload\Validators;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * MIME Validator.
 *
 * Validates file MIME type.
 *
 * @since 1.0.0
 */
class MimeValidator implements UploadValidator
{
    /**
     * Validate MIME type.
     *
     * @since 1.0.0
     *
     * @param array $file    File data.
     * @param array $options Validation options.
     * @return true|\WP_Error
     */
    public function validate($file, $options = array())
    {
        $fileType = $file['type'];
        $allowedMimes = isset($options['mimes']) ? $options['mimes'] : null;

        if ($allowedMimes === null) {
            $allowedMimes = get_allowed_mime_types();
        }

        // Check if MIME type is allowed
        $allowed = false;

        foreach ($allowedMimes as $mime) {
            if (is_array($mime)) {
                if (in_array($fileType, $mime, true)) {
                    $allowed = true;
                    break;
                }
            } elseif ($mime === $fileType) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            return new \WP_Error(
                'invalid_mime_type',
                sprintf(__('File type %s is not allowed', 'wp-api-codeia'), $fileType),
                array('status' => 400)
            );
        }

        // Verify file extension matches MIME type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!$this->extensionMatchesMime($extension, $fileType)) {
            return new \WP_Error(
                'mime_mismatch',
                __('File extension does not match file type', 'wp-api-codeia'),
                array('status' => 400)
            );
        }

        return true;
    }

    /**
     * Check if extension matches MIME type.
     *
     * @since 1.0.0
     *
     * @param string $extension File extension.
     * @param string $mimeType  MIME type.
     * @return bool
     */
    protected function extensionMatchesMime($extension, $mimeType)
    {
        $mimeMap = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'zip' => 'application/zip',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
        );

        $expectedMime = isset($mimeMap[$extension]) ? $mimeMap[$extension] : null;

        if ($expectedMime === null) {
            return true; // Allow unknown extensions
        }

        return strpos($mimeType, $expectedMime) === 0;
    }
}
