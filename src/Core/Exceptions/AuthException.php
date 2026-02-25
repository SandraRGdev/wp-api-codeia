<?php
/**
 * Authentication Exception
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Core\Exceptions;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

use Exception;

/**
 * Exception thrown for authentication errors.
 *
 * @since 1.0.0
 */
class AuthException extends Exception
{
    /**
     * Create an authentication exception from an error code.
     *
     * @since 1.0.0
     *
     * @param string $code Error code.
     * @param string $message Error message.
     * @return static
     */
    public static function fromCode($code, $message = '')
    {
        if (empty($message)) {
            $message = wp_api_codeia_get_auth_error_message($code);
        }

        return new static($message, 0, null);
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
        return new \WP_Error($this->getCode(), $this->getMessage());
    }
}

/**
 * Get human-readable error message for error code.
 *
 * @since 1.0.0
 *
 * @param string $code Error code.
 * @return string Error message.
 */
function wp_api_codeia_get_auth_error_message($code)
{
    $messages = array(
        WP_API_CODEIA_ERROR_AUTH_MISSING => __('No se proporcionaron credenciales de autenticación.', 'wp-api-codeia'),
        WP_API_CODEIA_ERROR_AUTH_INVALID => __('Credenciales de autenticación inválidas.', 'wp-api-codeia'),
        WP_API_CODEIA_ERROR_AUTH_EXPIRED => __('El token de autenticación ha expirado.', 'wp-api-codeia'),
        WP_API_CODEIA_ERROR_AUTH_REVOKED => __('El token de autenticación ha sido revocado.', 'wp-api-codeia'),
        WP_API_CODEIA_ERROR_FORBIDDEN => __('No tienes permisos para acceder a este recurso.', 'wp-api-codeia'),
    );

    return isset($messages[$code]) ? $messages[$code] : __('Error de autenticación desconocido.', 'wp-api-codeia');
}
