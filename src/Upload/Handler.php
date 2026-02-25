<?php
/**
 * Upload Handler
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Upload;

use WP_API_Codeia\Core\Interfaces\ServiceInterface;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Upload Handler.
 *
 * Handles file uploads with validation, security checks,
 * and WordPress media integration.
 *
 * @since 1.0.0
 */
class Handler implements ServiceInterface
{
    /**
     * Logger instance.
     *
     * @since 1.0.0
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Allowed MIME types.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $allowedMimeTypes = array();

    /**
     * Maximum file size.
     *
     * @since 1.0.0
     *
     * @var int
     */
    protected $maxFileSize;

    /**
     * Upload validators.
     *
     * @since 1.0.0
     *
     * @var array
     */
    protected $validators = array();

    /**
     * Create a new Upload Handler instance.
     *
     * @since 1.0.0
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
        $this->maxFileSize = $this->getMaxUploadSize();
    }

    /**
     * Register the upload service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        $this->initializeValidators();
    }

    /**
     * Boot the upload service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function boot()
    {
        // No boot needed
    }

    /**
     * Initialize upload validators.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function initializeValidators()
    {
        $this->validators = array(
            'size' => new Validators\SizeValidator($this->maxFileSize),
            'mime' => new Validators\MimeValidator(),
            'extension' => new Validators\ExtensionValidator(),
            'image' => new Validators\ImageValidator(),
            'security' => new Validators\SecurityValidator(),
        );
    }

    /**
     * Handle file upload.
     *
     * @since 1.0.0
     *
     * @param array $file    $_FILES array item.
     * @param array $options Upload options.
     * @return array|\WP_Error Attachment data or error.
     */
    public function handleUpload($file, $options = array())
    {
        $defaults = array(
            'test_form' => false,
            'test_size' => true,
            'test_upload' => true,
            'mimes' => null,
            'allowed_extensions' => array(),
            'max_file_size' => $this->maxFileSize,
            'validate_image' => true,
        );

        $options = wp_parse_args($options, $defaults);

        // Run custom validators
        $validation = $this->validate($file, $options);

        if (is_wp_error($validation)) {
            return $validation;
        }

        // Upload to WordPress
        $upload = wp_handle_upload($file, $options);

        if (isset($upload['error'])) {
            return new \WP_Error(
                'upload_failed',
                $upload['error'],
                array('status' => 400)
            );
        }

        // Create attachment
        $attachment = $this->createAttachment($upload, $options);

        if (is_wp_error($attachment)) {
            // Delete uploaded file on failure
            unlink($upload['file']);
            return $attachment;
        }

        $this->logger->info('File uploaded successfully', array(
            'attachment_id' => $attachment,
            'file' => $upload['file'],
        ));

        return array(
            'id' => $attachment,
            'url' => $upload['url'],
            'type' => $upload['type'],
            'file' => $upload['file'],
        );
    }

    /**
     * Handle multiple file uploads.
     *
     * @since 1.0.0
     *
     * @param array $files   $_FILES array.
     * @param array $options Upload options.
     * @return array|\WP_Error Array of results or error.
     */
    public function handleMultiple($files, $options = array())
    {
        $results = array();
        $errors = array();

        // Reorganize files array
        $filesArray = $this->reorganizeFilesArray($files);

        foreach ($filesArray as $index => $file) {
            $result = $this->handleUpload($file, $options);

            if (is_wp_error($result)) {
                $errors[] = array(
                    'index' => $index,
                    'error' => $result->get_error_message(),
                );
            } else {
                $results[] = $result;
            }
        }

        if (!empty($errors) && empty($results)) {
            return new \WP_Error(
                'upload_failed',
                __('All files failed to upload', 'wp-api-codeia'),
                array('errors' => $errors)
            );
        }

        return array(
            'uploaded' => $results,
            'errors' => $errors,
            'total' => count($filesArray),
            'success_count' => count($results),
            'error_count' => count($errors),
        );
    }

    /**
     * Reorganize multiple files array.
     *
     * @since 1.0.0
     *
     * @param array $files $_FILES array.
     * @return array
     */
    protected function reorganizeFilesArray($files)
    {
        $reorganized = array();

        foreach ($files as $key => $values) {
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $index => $value) {
                if (!isset($reorganized[$index])) {
                    $reorganized[$index] = array();
                }
                $reorganized[$index][$key] = $value;
            }
        }

        return $reorganized;
    }

    /**
     * Validate uploaded file.
     *
     * @since 1.0.0
     *
     * @param array $file    File data.
     * @param array $options Validation options.
     * @return true|\WP_Error
     */
    protected function validate($file, $options)
    {
        // Check for upload errors
        if (isset($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            return $this->getUploadError($file['error']);
        }

        // Check if file was uploaded
        if (!is_uploaded_file($file['tmp_name'])) {
            return new \WP_Error(
                'upload_failed',
                __('No file was uploaded or file upload failed', 'wp-api-codeia'),
                array('status' => 400)
            );
        }

        // Run validators
        foreach ($this->validators as $name => $validator) {
            if (method_exists($validator, 'validate')) {
                $result = $validator->validate($file, $options);

                if (is_wp_error($result)) {
                    return $result;
                }
            }
        }

        // Allow custom validation
        $customValidation = apply_filters('wp_api_codeia_validate_upload', true, $file, $options);

        if (is_wp_error($customValidation)) {
            return $customValidation;
        }

        return true;
    }

    /**
     * Create WordPress attachment.
     *
     * @since 1.0.0
     *
     * @param array $upload  Upload data from wp_handle_upload.
     * @param array $options Upload options.
     * @return int|\WP_Error Attachment ID or error.
     */
    protected function createAttachment($upload, $options)
    {
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'guid' => $upload['url'],
            'post_title' => $this->generateAttachmentTitle($upload['file']),
            'post_content' => '',
            'post_status' => 'inherit',
        );

        // Extract title from options if provided
        if (isset($options['title'])) {
            $attachment['post_title'] = sanitize_text_field($options['title']);
        }

        // Extract caption from options if provided
        if (isset($options['caption'])) {
            $attachment['post_excerpt'] = sanitize_text_field($options['caption']);
        }

        // Extract description from options if provided
        if (isset($options['description'])) {
            $attachment['post_content'] = sanitize_textarea_field($options['description']);
        }

        // Extract alt text from options if provided
        $alt = isset($options['alt']) ? sanitize_text_field($options['alt']) : '';

        // Insert attachment
        $attachId = wp_insert_attachment($attachment, $upload['file']);

        if (is_wp_error($attachId)) {
            return $attachId;
        }

        // Generate attachment metadata
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $attachData = wp_generate_attachment_metadata($attachId, $upload['file']);
        wp_update_attachment_metadata($attachId, $attachData);

        // Save alt text
        if (!empty($alt)) {
            update_post_meta($attachId, '_wp_attachment_image_alt', $alt);
        }

        return $attachId;
    }

    /**
     * Generate attachment title from filename.
     *
     * @since 1.0.0
     *
     * @param string $filepath File path.
     * @return string
     */
    protected function generateAttachmentTitle($filepath)
    {
        $filename = basename($filepath);
        $title = pathinfo($filename, PATHINFO_FILENAME);

        // Sanitize and format title
        $title = preg_replace('/[-_]+/', ' ', $title);
        $title = ucwords($title);

        return sanitize_text_field($title);
    }

    /**
     * Get upload error message.
     *
     * @since 1.0.0
     *
     * @param int $code PHP upload error code.
     * @return \WP_Error
     */
    protected function getUploadError($code)
    {
        $errors = array(
            UPLOAD_ERR_INI_SIZE => __('The uploaded file exceeds the upload_max_filesize directive in php.ini', 'wp-api-codeia'),
            UPLOAD_ERR_FORM_SIZE => __('The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form', 'wp-api-codeia'),
            UPLOAD_ERR_PARTIAL => __('The uploaded file was only partially uploaded', 'wp-api-codeia'),
            UPLOAD_ERR_NO_FILE => __('No file was uploaded', 'wp-api-codeia'),
            UPLOAD_ERR_NO_TMP_DIR => __('Missing a temporary folder', 'wp-api-codeia'),
            UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk', 'wp-api-codeia'),
            UPLOAD_ERR_EXTENSION => __('A PHP extension stopped the file upload', 'wp-api-codeia'),
        );

        $message = isset($errors[$code]) ? $errors[$code] : __('Unknown upload error', 'wp-api-codeia');

        return new \WP_Error(
            'upload_error',
            $message,
            array('status' => 400)
        );
    }

    /**
     * Get maximum upload size.
     *
     * @since 1.0.0
     *
     * @return int
     */
    protected function getMaxUploadSize()
    {
        $uploadMax = $this->convertToBytes(ini_get('upload_max_filesize'));
        $postMax = $this->convertToBytes(ini_get('post_max_size'));

        return min($uploadMax, $postMax);
    }

    /**
     * Convert shorthand notation to bytes.
     *
     * @since 1.0.0
     *
     * @param string $value Shorthand value.
     * @return int
     */
    protected function convertToBytes($value)
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);

        $value = (int) $value;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * Get allowed MIME types.
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function getAllowedMimeTypes()
    {
        if (empty($this->allowedMimeTypes)) {
            $this->allowedMimeTypes = get_allowed_mime_types();
        }

        return $this->allowedMimeTypes;
    }

    /**
     * Set allowed MIME types.
     *
     * @since 1.0.0
     *
     * @param array $mimes MIME types.
     * @return void
     */
    public function setAllowedMimeTypes($mimes)
    {
        $this->allowedMimeTypes = $mimes;
    }

    /**
     * Add upload validator.
     *
     * @since 1.0.0
     *
     * @param string                     $name      Validator name.
     * @param Validators\UploadValidator $validator Validator instance.
     * @return void
     */
    public function addValidator($name, $validator)
    {
        $this->validators[$name] = $validator;
    }

    /**
     * Remove upload validator.
     *
     * @since 1.0.0
     *
     * @param string $name Validator name.
     * @return void
     */
    public function removeValidator($name)
    {
        unset($this->validators[$name]);
    }

    /**
     * Delete uploaded file.
     *
     * @since 1.0.0
     *
     * @param int $attachmentId Attachment ID.
     * @return bool|\WP_Error
     */
    public function deleteFile($attachmentId)
    {
        $result = wp_delete_attachment($attachmentId, true);

        if (!$result) {
            return new \WP_Error(
                'delete_failed',
                __('Could not delete file', 'wp-api-codeia'),
                array('status' => 500)
            );
        }

        $this->logger->info('File deleted', array(
            'attachment_id' => $attachmentId,
        ));

        return true;
    }

    /**
     * Get file URL.
     *
     * @since 1.0.0
     *
     * @param int    $attachmentId Attachment ID.
     * @param string $size          Image size.
     * @return string|false
     */
    public function getFileUrl($attachmentId, $size = 'full')
    {
        $url = wp_get_attachment_image_url($attachmentId, $size);

        if (!$url) {
            $url = wp_get_attachment_url($attachmentId);
        }

        return $url;
    }

    /**
     * Get file data for API response.
     *
     * @since 1.0.0
     *
     * @param int $attachmentId Attachment ID.
     * @return array|\WP_Error
     */
    public function getFileData($attachmentId)
    {
        $attachment = get_post($attachmentId);

        if (!$attachment || $attachment->post_type !== 'attachment') {
            return new \WP_Error(
                'file_not_found',
                __('File not found', 'wp-api-codeia'),
                array('status' => 404)
            );
        }

        $metadata = wp_get_attachment_metadata($attachmentId);

        $data = array(
            'id' => (int) $attachmentId,
            'date' => mysql_to_rfc3339($attachment->post_date),
            'modified' => mysql_to_rfc3339($attachment->post_modified),
            'title' => array(
                'raw' => $attachment->post_title,
                'rendered' => $attachment->post_title,
            ),
            'author' => (int) $attachment->post_author,
            'description' => array(
                'raw' => $attachment->post_content,
                'rendered' => apply_filters('the_content', $attachment->post_content),
            ),
            'caption' => array(
                'raw' => $attachment->post_excerpt,
            ),
            'alt_text' => get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
            'media_type' => wp_attachment_is_image($attachmentId) ? 'image' : 'file',
            'mime_type' => $attachment->post_mime_type,
            'url' => wp_get_attachment_url($attachmentId),
            'meta' => $metadata,
        );

        if (wp_attachment_is_image($attachmentId)) {
            $data['sizes'] = array();
            if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
                foreach ($metadata['sizes'] as $size => $sizeData) {
                    $data['sizes'][$size] = array(
                        'file' => $sizeData['file'],
                        'width' => $sizeData['width'],
                        'height' => $sizeData['height'],
                        'mime_type' => $sizeData['mime-type'],
                        'source_url' => wp_get_attachment_image_url($attachmentId, $size),
                    );
                }
            }
        }

        return $data;
    }
}
