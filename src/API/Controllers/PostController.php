<?php
/**
 * Post Controller
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\API\Controllers;

use WP_API_Codeia\Schema\Detector;
use WP_API_Codeia\API\ResponseFormatter;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Post Controller.
 *
 * Handles CRUD operations for post types.
 *
 * @since 1.0.0
 */
class PostController
{
    /**
     * Schema Detector instance.
     *
     * @since 1.0.0
     *
     * @var Detector
     */
    protected $detector;

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
     * Create a new Post Controller instance.
     *
     * @since 1.0.0
     *
     * @param Detector          $detector  Schema Detector.
     * @param ResponseFormatter $formatter Response Formatter.
     * @param Logger            $logger    Logger instance.
     */
    public function __construct(Detector $detector, ResponseFormatter $formatter, Logger $logger)
    {
        $this->detector = $detector;
        $this->formatter = $formatter;
        $this->logger = $logger;
    }

    /**
     * Get enabled fields for a post type.
     *
     * @since 1.0.0
     *
     * @param string $postType Post type slug.
     * @return array|null
     */
    protected function getEnabledFields($postType)
    {
        $fieldConfig = get_option('wp_api_codeia_fields', array());

        if (isset($fieldConfig[$postType])) {
            $fields = $fieldConfig[$postType];

            // Check if wildcard means all fields
            if (in_array('*', $fields, true)) {
                return null; // null means all fields
            }

            return $fields;
        }

        // Default to all native fields if not configured
        return null;
    }

    /**
     * Format post with field filtering.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post     Post object.
     * @param string   $postType Post type.
     * @return array
     */
    protected function formatPostWithFields($post, $postType)
    {
        $enabledFields = $this->getEnabledFields($postType);

        // If all fields enabled or not configured, return all
        if ($enabledFields === null) {
            return $this->formatter->formatPost($post);
        }

        return $this->formatter->formatPost($post, 'view', $enabledFields);
    }

    /**
     * Get items collection.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return \WP_REST_Response
     */
    public function getItems($request, $postType)
    {
        $page = $request->get_param('page') ?: 1;
        $perPage = $request->get_param('per_page') ?: 10;
        $search = $request->get_param('search');
        $orderby = $request->get_param('orderby') ?: 'date';
        $order = $request->get_param('order') ?: 'desc';
        $status = $request->get_param('status') ?: 'publish';

        $args = array(
            'post_type' => $postType,
            'paged' => $page,
            'posts_per_page' => $perPage,
            's' => $search,
            'orderby' => $orderby,
            'order' => $order,
            'post_status' => $status,
        );

        // Filter for custom query args
        $args = apply_filters('wp_api_codeia_get_items_args', $args, $request, $postType);

        $query = new \WP_Query($args);

        $items = array();

        foreach ($query->posts as $post) {
            $items[] = $this->formatPostWithFields($post, $postType);
        }

        $response = $this->formatter->collection(
            $items,
            (int) $query->found_posts,
            $page,
            $perPage
        );

        return rest_ensure_response($response);
    }

    /**
     * Get single item.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return \WP_REST_Response|\WP_Error
     */
    public function getItem($request, $postType)
    {
        $id = $request->get_param('id');

        $post = get_post($id);

        if (!$post || $post->post_type !== $postType) {
            return $this->formatter->notFoundError('Post');
        }

        $data = $this->formatPostWithFields($post, $postType);

        return rest_ensure_response($this->formatter->item($data));
    }

    /**
     * Create new item.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return \WP_REST_Response|\WP_Error
     */
    public function createItem($request, $postType)
    {
        $title = $request->get_param('title');
        $content = $request->get_param('content');
        $excerpt = $request->get_param('excerpt');
        $status = $request->get_param('status') ?: 'draft';
        $author = $request->get_param('author') ?: get_current_user_id();
        $slug = $request->get_param('slug');

        $postData = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_excerpt' => $excerpt,
            'post_status' => $status,
            'post_author' => $author,
            'post_name' => $slug,
            'post_type' => $postType,
        );

        $postId = wp_insert_post($postData, true);

        if (is_wp_error($postId)) {
            return $postId;
        }

        // Handle meta fields
        $meta = $request->get_param('meta');
        if (!empty($meta) && is_array($meta)) {
            foreach ($meta as $key => $value) {
                update_post_meta($postId, $key, $value);
            }
        }

        // Handle terms
        $terms = $request->get_param('terms');
        if (!empty($terms) && is_array($terms)) {
            foreach ($terms as $taxonomy => $termIds) {
                wp_set_object_terms($postId, $termIds, $taxonomy);
            }
        }

        $post = get_post($postId);
        $data = $this->formatPostWithFields($post, $postType);

        $this->logger->info('Post created', array(
            'post_id' => $postId,
            'post_type' => $postType,
            'user_id' => get_current_user_id(),
        ));

        return rest_ensure_response($this->formatter->created($data));
    }

    /**
     * Update item.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return \WP_REST_Response|\WP_Error
     */
    public function updateItem($request, $postType)
    {
        $id = $request->get_param('id');

        $post = get_post($id);

        if (!$post || $post->post_type !== $postType) {
            return $this->formatter->notFoundError('Post');
        }

        $postData = array(
            'ID' => $id,
        );

        if ($request->has_param('title')) {
            $postData['post_title'] = $request->get_param('title');
        }

        if ($request->has_param('content')) {
            $postData['post_content'] = $request->get_param('content');
        }

        if ($request->has_param('excerpt')) {
            $postData['post_excerpt'] = $request->get_param('excerpt');
        }

        if ($request->has_param('status')) {
            $postData['post_status'] = $request->get_param('status');
        }

        if ($request->has_param('slug')) {
            $postData['post_name'] = $request->get_param('slug');
        }

        $postId = wp_update_post($postData, true);

        if (is_wp_error($postId)) {
            return $postId;
        }

        // Handle meta fields
        $meta = $request->get_param('meta');
        if (!empty($meta) && is_array($meta)) {
            foreach ($meta as $key => $value) {
                update_post_meta($id, $key, $value);
            }
        }

        // Handle terms
        $terms = $request->get_param('terms');
        if (!empty($terms) && is_array($terms)) {
            foreach ($terms as $taxonomy => $termIds) {
                wp_set_object_terms($id, $termIds, $taxonomy);
            }
        }

        $post = get_post($id);
        $data = $this->formatPostWithFields($post, $postType);

        $this->logger->info('Post updated', array(
            'post_id' => $id,
            'post_type' => $postType,
            'user_id' => get_current_user_id(),
        ));

        return rest_ensure_response($this->formatter->updated($data));
    }

    /**
     * Delete item.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return \WP_REST_Response|\WP_Error
     */
    public function deleteItem($request, $postType)
    {
        $id = $request->get_param('id');
        $force = $request->get_param('force') ?: false;

        $post = get_post($id);

        if (!$post || $post->post_type !== $postType) {
            return $this->formatter->notFoundError('Post');
        }

        $result = wp_delete_post($id, $force);

        if (!$result) {
            return new \WP_Error(
                'delete_failed',
                __('Could not delete post', 'wp-api-codeia'),
                array('status' => 500)
            );
        }

        $data = $this->formatPostWithFields($post, $postType);

        $this->logger->info('Post deleted', array(
            'post_id' => $id,
            'post_type' => $postType,
            'user_id' => get_current_user_id(),
            'force' => $force,
        ));

        return rest_ensure_response($this->formatter->deleted($data));
    }

    /**
     * Get item meta.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return \WP_REST_Response|\WP_Error
     */
    public function getItemMeta($request, $postType)
    {
        $id = $request->get_param('id');

        $post = get_post($id);

        if (!$post || $post->post_type !== $postType) {
            return $this->formatter->notFoundError('Post');
        }

        $meta = get_post_meta($id);

        // Filter protected meta
        $publicMeta = array();
        foreach ($meta as $key => $values) {
            if (strpos($key, '_') === 0) {
                continue;
            }
            $publicMeta[$key] = count($values) > 1 ? $values : $values[0];
        }

        return rest_ensure_response($this->formatter->success($publicMeta));
    }

    /**
     * Update item meta.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return \WP_REST_Response|\WP_Error
     */
    public function updateItemMeta($request, $postType)
    {
        $id = $request->get_param('id');

        $post = get_post($id);

        if (!$post || $post->post_type !== $postType) {
            return $this->formatter->notFoundError('Post');
        }

        $meta = $request->get_json_params();

        if (empty($meta) || !is_array($meta)) {
            return $this->formatter->validationError(array(
                'meta' => __('Meta data is required', 'wp-api-codeia'),
            ));
        }

        foreach ($meta as $key => $value) {
            if (strpos($key, '_') === 0) {
                continue; // Skip protected meta
            }
            update_post_meta($id, $key, $value);
        }

        return rest_ensure_response($this->formatter->success(
            array('updated' => true),
            __('Meta updated successfully', 'wp-api-codeia')
        ));
    }

    /**
     * Get item terms.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return \WP_REST_Response|\WP_Error
     */
    public function getItemTerms($request, $postType)
    {
        $id = $request->get_param('id');
        $taxonomy = $request->get_param('taxonomy');

        $post = get_post($id);

        if (!$post || $post->post_type !== $postType) {
            return $this->formatter->notFoundError('Post');
        }

        if (!taxonomy_exists($taxonomy)) {
            return $this->formatter->notFoundError('Taxonomy');
        }

        $terms = wp_get_object_terms($id, $taxonomy);

        $formatted = array_map(function ($term) {
            return $this->formatter->formatTerm($term);
        }, $terms);

        return rest_ensure_response($this->formatter->success($formatted));
    }

    /**
     * Set item terms.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return \WP_REST_Response|\WP_Error
     */
    public function setItemTerms($request, $postType)
    {
        $id = $request->get_param('id');
        $taxonomy = $request->get_param('taxonomy');

        $post = get_post($id);

        if (!$post || $post->post_type !== $postType) {
            return $this->formatter->notFoundError('Post');
        }

        if (!taxonomy_exists($taxonomy)) {
            return $this->formatter->notFoundError('Taxonomy');
        }

        $params = $request->get_json_params();
        $termIds = isset($params['terms']) ? $params['terms'] : array();
        $append = isset($params['append']) ? (bool) $params['append'] : false;

        $result = wp_set_object_terms($id, $termIds, $taxonomy, $append);

        if (is_wp_error($result)) {
            return $result;
        }

        $terms = wp_get_object_terms($id, $taxonomy);

        $formatted = array_map(function ($term) {
            return $this->formatter->formatTerm($term);
        }, $terms);

        return rest_ensure_response($this->formatter->success($formatted));
    }

    /**
     * Check permission for get items.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return bool|\WP_Error
     */
    public function getItemsPermission($request, $postType)
    {
        $postTypeObject = get_post_type_object($postType);

        if (!$postTypeObject) {
            return false;
        }

        // Check if post type is public
        if (!$postTypeObject->public && !current_user_can($postTypeObject->cap->read_posts)) {
            return new \WP_Error(
                'rest_cannot_read',
                __('Sorry, you cannot read this post type.', 'wp-api-codeia'),
                array('status' => 401)
            );
        }

        return true;
    }

    /**
     * Check permission for get item.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return bool|\WP_Error
     */
    public function getItemPermission($request, $postType)
    {
        $id = $request->get_param('id');
        $post = get_post($id);

        if (!$post || $post->post_type !== $postType) {
            return true; // Will be handled in callback
        }

        $postTypeObject = get_post_type_object($postType);

        if (!$postTypeObject) {
            return false;
        }

        if ($post->post_author !== get_current_user_id()) {
            if (!current_user_can($postTypeObject->cap->read_others_posts)) {
                return $this->formatter->forbiddenError();
            }
        }

        return true;
    }

    /**
     * Check permission for create item.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return bool|\WP_Error
     */
    public function createItemPermission($request, $postType)
    {
        $postTypeObject = get_post_type_object($postType);

        if (!$postTypeObject) {
            return false;
        }

        if (!current_user_can($postTypeObject->cap->create_posts)) {
            return $this->formatter->forbiddenError();
        }

        return true;
    }

    /**
     * Check permission for update item.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return bool|\WP_Error
     */
    public function updateItemPermission($request, $postType)
    {
        $id = $request->get_param('id');
        $post = get_post($id);

        if (!$post || $post->post_type !== $postType) {
            return true; // Will be handled in callback
        }

        $postTypeObject = get_post_type_object($postType);

        if (!$postTypeObject) {
            return false;
        }

        if ($post->post_author !== get_current_user_id()) {
            if (!current_user_can($postTypeObject->cap->edit_others_posts)) {
                return $this->formatter->forbiddenError();
            }
        }

        return true;
    }

    /**
     * Check permission for delete item.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $postType Post type.
     * @return bool|\WP_Error
     */
    public function deleteItemPermission($request, $postType)
    {
        $id = $request->get_param('id');
        $post = get_post($id);

        if (!$post || $post->post_type !== $postType) {
            return true; // Will be handled in callback
        }

        $postTypeObject = get_post_type_object($postType);

        if (!$postTypeObject) {
            return false;
        }

        if ($post->post_author !== get_current_user_id()) {
            if (!current_user_can($postTypeObject->cap->delete_others_posts)) {
                return $this->formatter->forbiddenError();
            }
        }

        return true;
    }
}
