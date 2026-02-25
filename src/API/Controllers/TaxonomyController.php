<?php
/**
 * Taxonomy Controller
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
 * Taxonomy Controller.
 *
 * Handles CRUD operations for taxonomies.
 *
 * @since 1.0.0
 */
class TaxonomyController
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
     * Create a new Taxonomy Controller instance.
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
     * Get terms collection.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $taxonomy Taxonomy.
     * @return \WP_REST_Response
     */
    public function getTerms($request, $taxonomy)
    {
        $page = $request->get_param('page') ?: 1;
        $perPage = $request->get_param('per_page') ?: 10;
        $search = $request->get_param('search');
        $orderby = $request->get_param('orderby') ?: 'name';
        $order = $request->get_param('order') ?: 'asc';
        $hideEmpty = $request->get_param('hide_empty') ?: false;

        $args = array(
            'taxonomy' => $taxonomy,
            'number' => $perPage,
            'offset' => ($page - 1) * $perPage,
            'search' => $search,
            'orderby' => $orderby,
            'order' => $order,
            'hide_empty' => $hideEmpty,
        );

        $terms = get_terms($args);

        if (is_wp_error($terms)) {
            return $terms;
        }

        $total = wp_count_terms($taxonomy, array('hide_empty' => false));

        $items = array_map(function ($term) {
            return $this->formatter->formatTerm($term);
        }, $terms);

        $response = $this->formatter->collection(
            $items,
            (int) $total,
            $page,
            $perPage
        );

        return rest_ensure_response($response);
    }

    /**
     * Get single term.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $taxonomy Taxonomy.
     * @return \WP_REST_Response|\WP_Error
     */
    public function getTerm($request, $taxonomy)
    {
        $id = $request->get_param('id');

        $term = get_term($id, $taxonomy);

        if (is_wp_error($term)) {
            return $term;
        }

        if (!$term || $term->taxonomy !== $taxonomy) {
            return $this->formatter->notFoundError('Term');
        }

        $data = $this->formatter->formatTerm($term);

        return rest_ensure_response($this->formatter->item($data));
    }

    /**
     * Create new term.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $taxonomy Taxonomy.
     * @return \WP_REST_Response|\WP_Error
     */
    public function createTerm($request, $taxonomy)
    {
        $name = $request->get_param('name');
        $description = $request->get_param('description') ?: '';
        $parent = $request->get_param('parent') ?: 0;
        $slug = $request->get_param('slug');

        $termData = array(
            'taxonomy' => $taxonomy,
            'name' => $name,
            'description' => $description,
            'parent' => $parent,
            'slug' => $slug,
        );

        $result = wp_insert_term($name, $taxonomy, $termData);

        if (is_wp_error($result)) {
            return $result;
        }

        $termId = $result['term_id'];
        $term = get_term($termId, $taxonomy);

        $data = $this->formatter->formatTerm($term);

        $this->logger->info('Term created', array(
            'term_id' => $termId,
            'taxonomy' => $taxonomy,
            'user_id' => get_current_user_id(),
        ));

        return rest_ensure_response($this->formatter->created($data));
    }

    /**
     * Update term.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $taxonomy Taxonomy.
     * @return \WP_REST_Response|\WP_Error
     */
    public function updateTerm($request, $taxonomy)
    {
        $id = $request->get_param('id');

        $term = get_term($id, $taxonomy);

        if (is_wp_error($term)) {
            return $term;
        }

        if (!$term || $term->taxonomy !== $taxonomy) {
            return $this->formatter->notFoundError('Term');
        }

        $termData = array(
            'term_id' => $id,
            'taxonomy' => $taxonomy,
        );

        if ($request->has_param('name')) {
            $termData['name'] = $request->get_param('name');
        }

        if ($request->has_param('description')) {
            $termData['description'] = $request->get_param('description');
        }

        if ($request->has_param('slug')) {
            $termData['slug'] = $request->get_param('slug');
        }

        if ($request->has_param('parent')) {
            $termData['parent'] = $request->get_param('parent');
        }

        $result = wp_update_term($id, $taxonomy, $termData);

        if (is_wp_error($result)) {
            return $result;
        }

        $term = get_term($id, $taxonomy);
        $data = $this->formatter->formatTerm($term);

        $this->logger->info('Term updated', array(
            'term_id' => $id,
            'taxonomy' => $taxonomy,
            'user_id' => get_current_user_id(),
        ));

        return rest_ensure_response($this->formatter->updated($data));
    }

    /**
     * Delete term.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $taxonomy Taxonomy.
     * @return \WP_REST_Response|\WP_Error
     */
    public function deleteTerm($request, $taxonomy)
    {
        $id = $request->get_param('id');

        $term = get_term($id, $taxonomy);

        if (is_wp_error($term)) {
            return $term;
        }

        if (!$term || $term->taxonomy !== $taxonomy) {
            return $this->formatter->notFoundError('Term');
        }

        $result = wp_delete_term($id, $taxonomy);

        if (is_wp_error($result)) {
            return $result;
        }

        if (!$result) {
            return new \WP_Error(
                'delete_failed',
                __('Could not delete term', 'wp-api-codeia'),
                array('status' => 500)
            );
        }

        $data = $this->formatter->formatTerm($term);

        $this->logger->info('Term deleted', array(
            'term_id' => $id,
            'taxonomy' => $taxonomy,
            'user_id' => get_current_user_id(),
        ));

        return rest_ensure_response($this->formatter->deleted($data));
    }

    /**
     * Check permission for create term.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $taxonomy Taxonomy.
     * @return bool|\WP_Error
     */
    public function createTermPermission($request, $taxonomy)
    {
        $taxonomyObject = get_taxonomy($taxonomy);

        if (!$taxonomyObject) {
            return false;
        }

        if (!current_user_can($taxonomyObject->cap->manage_terms)) {
            return $this->formatter->forbiddenError();
        }

        return true;
    }

    /**
     * Check permission for update term.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $taxonomy Taxonomy.
     * @return bool|\WP_Error
     */
    public function updateTermPermission($request, $taxonomy)
    {
        $taxonomyObject = get_taxonomy($taxonomy);

        if (!$taxonomyObject) {
            return false;
        }

        if (!current_user_can($taxonomyObject->cap->edit_terms)) {
            return $this->formatter->forbiddenError();
        }

        return true;
    }

    /**
     * Check permission for delete term.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request  Request object.
     * @param string           $taxonomy Taxonomy.
     * @return bool|\WP_Error
     */
    public function deleteTermPermission($request, $taxonomy)
    {
        $taxonomyObject = get_taxonomy($taxonomy);

        if (!$taxonomyObject) {
            return false;
        }

        if (!current_user_can($taxonomyObject->cap->delete_terms)) {
            return $this->formatter->forbiddenError();
        }

        return true;
    }
}
