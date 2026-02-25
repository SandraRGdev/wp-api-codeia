<?php
/**
 * Validation Exception
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Core\Exceptions;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Exception thrown for validation errors.
 *
 * @since 1.0.0
 */
class ValidationException extends \Exception
{
    /**
     * Validation errors.
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Create a validation exception.
     *
     * @since 1.0.0
     *
     * @param array $errors Validation errors.
     * @return static
     */
    public static function withErrors(array $errors)
    {
        $exception = new static(__('Validación fallida.', 'wp-api-codeia'));
        $exception->errors = $errors;

        return $exception;
    }

    /**
     * Get validation errors.
     *
     * @since 1.0.0
     *
     * @return array Validation errors.
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Convert exception to WP_Error.
     *
     * @since 1.0.0
     *
     * @return \WP_Error WordPress error object.
     */
    public function to_wp_error()
    {
        $error = new \WP_Error(
            WP_API_CODEIA_ERROR_VALIDATION_FAILED,
            $this->getMessage()
        );

        foreach ($this->errors as $field => $message) {
            $error->add($field, $message);
        }

        return $error;
    }
}
