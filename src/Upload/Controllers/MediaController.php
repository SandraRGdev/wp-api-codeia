<?php
/**
 * Media Controller
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Upload\Controllers;

use WP_API_Codeia\Upload\Handler;
use WP_API_Codeia\API\ResponseFormatter;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Media Controller.
 *
 * Handles media upload endpoints.
 *
 * @since 1.0.0
 */
class MediaController
{
    /**
     * Upload Handler instance.
     *
     * @since 1.0.0
     *
     * @var Handler
     */
    protected $handler;

    /**
     * Response Formatter instance.
     *
     * @since 1.0.0
     *
     * @var ResponseFormatter
     */
    protected $formatter;

    /**
     * Logger instance.
     *
     * @since 1.0.0
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Create a new Media Controller instance.
     *
     * @since 1.0.0
     *
     * @param Handler          $handler   Upload Handler.
     * @param ResponseFormatter $formatter Response Formatter.
     * @param Logger            $logger    Logger instance.
     */
    public function __construct(Handler $handler, ResponseFormatter $formatter, Logger $logger)
    {
        $this->handler = $handler;
        $this->formatter = $formatter;
        $this->logger = $logger;
    }

    /**
     * Upload a single file.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function upload($request)
    {
        $files = $request->get_file_params();

        if (empty($files)) {
            return $this->formatter->error(
                'no_file',
                __('No file provided', 'wp-api-codeia'),
                400
            );
        }

        $file = reset($files);

        // Get upload options from request
        $options = array(
            'title' => $request->get_param('title'),
            'caption' => $request->get_param('caption'),
            'description' => $request->get_param('description'),
            'alt' => $request->get_param('alt'),
            'allowed_extensions' => $request->get_param('allowed_extensions'),
        );

        $result = $this->handler->handleUpload($file, $options);

        if (is_wp_error($result)) {
            return $result;
        }

        // Get full attachment data
        $data = $this->handler->getFileData($result['id']);

        return rest_ensure_response($this->formatter->created($data));
    }

    /**
     * Upload multiple files.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function uploadMultiple($request)
    {
        $files = $request->get_file_params();

        if (empty($files)) {
            return $this->formatter->error(
                'no_file',
                __('No files provided', 'wp-api-codeia'),
                400
            );
        }

        $options = array(
            'allowed_extensions' => $request->get_param('allowed_extensions'),
        );

        $result = $this->handler->handleMultiple($files, $options);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($this->formatter->success($result));
    }

    /**
     * Get media item.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function getItem($request)
    {
        $id = $request->get_param('id');

        $data = $this->handler->getFileData($id);

        if (is_wp_error($data)) {
            return $data;
        }

        return rest_ensure_response($this->formatter->item($data));
    }

    /**
     * Update media item.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function updateItem($request)
    {
        $id = $request->get_param('id');

        $attachment = array(
            'ID' => $id,
            'post_title' => $request->get_param('title'),
            'post_excerpt' => $request->get_param('caption'),
            'post_content' => $request->get_param('description'),
        );

        // Remove null values
        $attachment = array_filter($attachment, function ($value) {
            return $value !== null;
        });

        $updated = wp_update_attachment_metadata($id, $attachment);

        if (is_wp_error($updated)) {
            return $updated;
        }

        // Update alt text
        if ($request->has_param('alt')) {
            update_post_meta($id, '_wp_attachment_image_alt', $request->get_param('alt'));
        }

        $data = $this->handler->getFileData($id);

        return rest_ensure_response($this->formatter->updated($data));
    }

    /**
     * Delete media item.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function deleteItem($request)
    {
        $id = $request->get_param('id');
        $force = $request->get_param('force') ?: false;

        $result = $this->handler->deleteFile($id);

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response($this->formatter->deleted(array('id' => $id)));
    }

    /**
     * Check upload permission.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return bool|\WP_Error
     */
    public function uploadPermission($request)
    {
        if (!current_user_can('upload_files')) {
            return $this->formatter->forbiddenError();
        }

        return true;
    }

    /**
     * Check edit permission.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return bool|\WP_Error
     */
    public function editItemPermission($request)
    {
        $id = $request->get_param('id');

        if (!current_user_can('edit_post', $id)) {
            return $this->formatter->forbiddenError();
        }

        return true;
    }

    /**
     * Check delete permission.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request Request object.
     * @return bool|\WP_Error
     */
    public function deleteItemPermission($request)
    {
        $id = $request->get_param('id');

        if (!current_user_can('delete_post', $id)) {
            return $this->formatter->forbiddenError();
        }

        return true;
    }
}
