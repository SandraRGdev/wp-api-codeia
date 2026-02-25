<?php
/**
 * Response Formatter
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\API;

use WP_API_Codeia\Core\Interfaces\ServiceInterface;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Response Formatter.
 *
 * Formats API responses according to the plugin's JSON schema.
 *
 * @since 1.0.0
 */
class ResponseFormatter implements ServiceInterface
{
    /**
     * API version.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $version;

    /**
     * Create a new Response Formatter instance.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->version = WP_API_CODEIA_API_VERSION;
    }

    /**
     * Register the formatter service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register()
    {
        // No registration needed
    }

    /**
     * Boot the formatter service.
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
     * Format success response.
     *
     * @since 1.0.0
     *
     * @param mixed  $data    Response data.
     * @param string $message Optional message.
     * @param array  $meta    Additional meta data.
     * @return array
     */
    public function success($data, $message = '', $meta = array())
    {
        $response = array(
            'success' => true,
            'data' => $data,
            'meta' => $this->buildMeta($meta),
        );

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return $response;
    }

    /**
     * Format error response.
     *
     * @since 1.0.0
     *
     * @param string|\WP_Error $code    Error code or WP_Error.
     * @param string           $message Error message.
     * @param int              $status  HTTP status code.
     * @param array            $data    Additional error data.
     * @return \WP_Error
     */
    public function error($code, $message = '', $status = 400, $data = array())
    {
        if (is_wp_error($code)) {
            return $code;
        }

        return new \WP_Error($code, $message, array_merge(array('status' => $status), $data));
    }

    /**
     * Format collection response with pagination.
     *
     * @since 1.0.0
     *
     * @param array  $items    Collection items.
     * @param int    $total    Total items.
     * @param int    $page     Current page.
     * @param int    $perPage  Items per page.
     * @param string $message  Optional message.
     * @return array
     */
    public function collection($items, $total, $page, $perPage, $message = '')
    {
        $data = array(
            'items' => $items,
            'pagination' => array(
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total_items' => (int) $total,
                'total_pages' => (int) ceil($total / $perPage),
            ),
        );

        return $this->success($data, $message);
    }

    /**
     * Format item response.
     *
     * @since 1.0.0
     *
     * @param array  $item    Item data.
     * @param string $message Optional message.
     * @return array
     */
    public function item($item, $message = '')
    {
        return $this->success($item, $message);
    }

    /**
     * Format created response.
     *
     * @since 1.0.0
     *
     * @param array  $item    Created item data.
     * @param string $message Optional message.
     * @return array
     */
    public function created($item, $message = '')
    {
        if (empty($message)) {
            $message = __('Resource created successfully', 'wp-api-codeia');
        }

        return $this->success($item, $message);
    }

    /**
     * Format updated response.
     *
     * @since 1.0.0
     *
     * @param array  $item    Updated item data.
     * @param string $message Optional message.
     * @return array
     */
    public function updated($item, $message = '')
    {
        if (empty($message)) {
            $message = __('Resource updated successfully', 'wp-api-codeia');
        }

        return $this->success($item, $message);
    }

    /**
     * Format deleted response.
     *
     * @since 1.0.0
     *
     * @param array  $item    Deleted item data.
     * @param string $message Optional message.
     * @return array
     */
    public function deleted($item, $message = '')
    {
        if (empty($message)) {
            $message = __('Resource deleted successfully', 'wp-api-codeia');
        }

        return $this->success($item, $message);
    }

    /**
     * Format post object for API response.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post     Post object.
     * @param string   $context  Context (view, edit).
     * @param array    $fields   Fields to include.
     * @return array
     */
    public function formatPost($post, $context = 'view', $fields = null)
    {
        $data = array(
            'id' => (int) $post->ID,
            'title' => array(
                'raw' => $post->post_title,
                'rendered' => get_the_title($post->ID),
            ),
            'content' => array(
                'raw' => $post->post_content,
                'rendered' => apply_filters('the_content', $post->post_content),
            ),
            'excerpt' => array(
                'raw' => $post->post_excerpt,
                'rendered' => apply_filters('get_the_excerpt', $post->post_excerpt, $post),
            ),
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'type' => $post->post_type,
            'author' => (int) $post->post_author,
            'date' => $this->formatDate($post->post_date),
            'date_gmt' => $this->formatDate($post->post_date_gmt),
            'modified' => $this->formatDate($post->post_modified),
            'modified_gmt' => $this->formatDate($post->post_modified_gmt),
            'parent' => (int) $post->post_parent,
            'link' => get_permalink($post->ID),
            'meta' => $this->getPostMeta($post->ID),
        );

        // Add thumbnail if supported
        if (post_type_supports($post->post_type, 'thumbnail')) {
            $thumbnailId = get_post_thumbnail_id($post->ID);

            if ($thumbnailId) {
                $data['featured_media'] = (int) $thumbnailId;
                $data['featured_media_url'] = wp_get_attachment_image_url($thumbnailId, 'full');
            }
        }

        // Filter fields if specified
        if ($fields !== null) {
            $data = array_intersect_key($data, array_flip($fields));
        }

        return apply_filters('wp_api_codeia_format_post', $data, $post, $context);
    }

    /**
     * Format term object for API response.
     *
     * @since 1.0.0
     *
     * @param \WP_Term $term    Term object.
     * @param string   $context Context (view, edit).
     * @return array
     */
    public function formatTerm($term, $context = 'view')
    {
        $data = array(
            'id' => (int) $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'taxonomy' => $term->taxonomy,
            'description' => $term->description,
            'parent' => (int) $term->parent,
            'count' => (int) $term->count,
            'link' => get_term_link($term),
        );

        return apply_filters('wp_api_codeia_format_term', $data, $term, $context);
    }

    /**
     * Format date for API response.
     *
     * @since 1.0.0
     *
     * @param string $date Date string.
     * @return string|null
     */
    protected function formatDate($date)
    {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            return null;
        }

        return mysql_to_rfc3339($date);
    }

    /**
     * Get post meta for API response.
     *
     * @since 1.0.0
     *
     * @param int $postId Post ID.
     * @return array
     */
    protected function getPostMeta($postId)
    {
        $meta = array();

        // Get public meta (keys not starting with _)
        $allMeta = get_post_meta($postId);

        foreach ($allMeta as $key => $values) {
            if (strpos($key, '_') === 0) {
                continue;
            }

            $meta[$key] = count($values) > 1 ? $values : $values[0];
        }

        return $meta;
    }

    /**
     * Build meta object for response.
     *
     * @since 1.0.0
     *
     * @param array $additional Additional meta data.
     * @return array
     */
    protected function buildMeta($additional = array())
    {
        $meta = array_merge(array(
            'timestamp' => current_time('mysql'),
            'request_id' => wp_generate_uuid4(),
            'version' => $this->version,
        ), $additional);

        return apply_filters('wp_api_codeia_response_meta', $meta);
    }

    /**
     * Prepare response for REST API.
     *
     * @since 1.0.0
     *
     * @param array  $response Formatted response.
     * @param int    $status   HTTP status code.
     * @return \WP_REST_Response
     */
    public function prepareResponse($response, $status = 200)
    {
        return new \WP_REST_Response($response, $status);
    }

    /**
     * Send error response.
     *
     * @since 1.0.0
     *
     * @param string|\WP_Error $code    Error code or WP_Error.
     * @param string           $message Error message.
     * @param int              $status  HTTP status code.
     * @return \WP_Error
     */
    public function sendError($code, $message = '', $status = 400)
    {
        return $this->error($code, $message, $status);
    }

    /**
     * Format validation error.
     *
     * @since 1.0.0
     *
     * @param array $errors Validation errors.
     * @return \WP_Error
     */
    public function validationError($errors)
    {
        return new \WP_Error(
            WP_API_CODEIA_ERROR_VALIDATION_FAILED,
            __('Validation failed', 'wp-api-codeia'),
            array(
                'status' => 400,
                'errors' => $errors,
            )
        );
    }

    /**
     * Format not found error.
     *
     * @since 1.0.0
     *
     * @param string $resource Resource type.
     * @return \WP_Error
     */
    public function notFoundError($resource = 'Resource')
    {
        return new \WP_Error(
            WP_API_CODEIA_ERROR_NOT_FOUND,
            sprintf(__('%s not found', 'wp-api-codeia'), $resource),
            array('status' => 404)
        );
    }

    /**
     * Format forbidden error.
     *
     * @since 1.0.0
     *
     * @param string $message Error message.
     * @return \WP_Error
     */
    public function forbiddenError($message = '')
    {
        if (empty($message)) {
            $message = __('You do not have permission to perform this action', 'wp-api-codeia');
        }

        return new \WP_Error(
            WP_API_CODEIA_ERROR_FORBIDDEN,
            $message,
            array('status' => 403)
        );
    }
}
