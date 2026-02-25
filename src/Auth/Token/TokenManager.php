<?php
/**
 * Token Manager
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Auth\Token;

use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Token Manager.
 *
 * Manages JWT tokens including issuing, validating, storing,
 * and blacklisting.
 *
 * @since 1.0.0
 */
class TokenManager
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
     * Blacklist cache key.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $blacklistCacheKey = 'codeia_token_blacklist';

    /**
     * Create a new Token Manager instance.
     *
     * @since 1.0.0
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Issue a new JWT token.
     *
     * @since 1.0.0
     *
     * @param array $payload Token payload.
     * @return string JWT token.
     */
    public function issue(array $payload)
    {
        $header = array(
            'typ' => 'JWT',
            'alg' => WP_API_CODEIA_JWT_ALGORITHM,
        );

        $segments = array();
        $segments[] = $this->base64UrlEncode(json_encode($header));
        $segments[] = $this->base64UrlEncode(json_encode($payload));

        $signingInput = implode('.', $segments);
        $signature = $this->sign($signingInput);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * Validate a JWT token.
     *
     * @since 1.0.0
     *
     * @param string $token JWT token.
     * @return array|\WP_Error Payload or error.
     */
    public function validate($token)
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid token format',
                array('status' => 401)
            );
        }

        list($header64, $payload64, $signature64) = $segments;

        // Decode header and payload
        $header = json_decode($this->base64UrlDecode($header64), true);
        $payload = json_decode($this->base64UrlDecode($payload64), true);

        if (!is_array($header) || !is_array($payload)) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid token encoding',
                array('status' => 401)
            );
        }

        // Verify algorithm
        if (!isset($header['alg']) || $header['alg'] !== WP_API_CODEIA_JWT_ALGORITHM) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid token algorithm',
                array('status' => 401)
            );
        }

        // Verify signature
        $signingInput = $header64 . '.' . $payload64;
        $expectedSignature = $this->base64UrlDecode($signature64);

        if (!$this->verify($signingInput, $expectedSignature)) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid token signature',
                array('status' => 401)
            );
        }

        // Check expiration
        if (isset($payload['exp']) && time() >= $payload['exp']) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_EXPIRED,
                'Token has expired',
                array('status' => 401)
            );
        }

        // Check not before
        if (isset($payload['nbf']) && time() < $payload['nbf']) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Token not yet valid',
                array('status' => 401)
            );
        }

        return $payload;
    }

    /**
     * Sign data.
     *
     * @since 1.0.0
     *
     * @param string $data Data to sign.
     * @return string Signature.
     */
    protected function sign($data)
    {
        $key = $this->getPrivateKey();
        $algorithm = $this->getOpenSslAlgorithm();

        openssl_sign($data, $signature, $key, $algorithm);

        return $signature;
    }

    /**
     * Verify signature.
     *
     * @since 1.0.0
     *
     * @param string $data      Signed data.
     * @param string $signature Signature to verify.
     * @return bool True if valid.
     */
    protected function verify($data, $signature)
    {
        $key = $this->getPublicKey();
        $algorithm = $this->getOpenSslAlgorithm();

        $result = openssl_verify($data, $signature, $key, $algorithm);

        return $result === 1;
    }

    /**
     * Get private key for signing.
     *
     * @since 1.0.0
     *
     * @return resource|false
     */
    protected function getPrivateKey()
    {
        $key = $this->getOrCreateKeyPair();
        return openssl_get_privatekey($key['private']);
    }

    /**
     * Get public key for verification.
     *
     * @since 1.0.0
     *
     * @return resource|false
     */
    protected function getPublicKey()
    {
        $key = $this->getOrCreateKeyPair();
        return openssl_get_publickey($key['public']);
    }

    /**
     * Get or create RSA key pair.
     *
     * @since 1.0.0
     *
     * @return array Array with 'private' and 'public' keys.
     */
    protected function getOrCreateKeyPair()
    {
        $keys = get_option('codeia_jwt_keys');

        if (!$keys || !isset($keys['private']) || !isset($keys['public'])) {
            $keys = $this->generateKeyPair();
            update_option('codeia_jwt_keys', $keys, false);
        }

        return $keys;
    }

    /**
     * Generate RSA key pair.
     *
     * @since 1.0.0
     *
     * @return array Array with 'private' and 'public' keys.
     */
    protected function generateKeyPair()
    {
        $config = array(
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        );

        $keyPair = openssl_pkey_new($config);
        openssl_pkey_export($keyPair, $privateKey);
        $publicKey = openssl_pkey_get_details($keyPair);
        $publicKey = $publicKey['key'];

        return array(
            'private' => $privateKey,
            'public' => $publicKey,
            'created' => time(),
        );
    }

    /**
     * Get OpenSSL algorithm constant.
     *
     * @since 1.0.0
     *
     * @return int
     */
    protected function getOpenSslAlgorithm()
    {
        $algorithms = array(
            'RS256' => OPENSSL_ALGO_SHA256,
            'RS384' => OPENSSL_ALGO_SHA384,
            'RS512' => OPENSSL_ALGO_SHA512,
        );

        return isset($algorithms[WP_API_CODEIA_JWT_ALGORITHM])
            ? $algorithms[WP_API_CODEIA_JWT_ALGORITHM]
            : OPENSSL_ALGO_SHA256;
    }

    /**
     * Base64 URL encode.
     *
     * @since 1.0.0
     *
     * @param string $data Data to encode.
     * @return string Encoded data.
     */
    protected function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode.
     *
     * @since 1.0.0
     *
     * @param string $data Data to decode.
     * @return string Decoded data.
     */
    protected function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Store token in database.
     *
     * @since 1.0.0
     *
     * @param string $token      JWT token.
     * @param int    $user_id    User ID.
     * @param string $token_type Token type (access/refresh).
     * @param int    $expires_at Expiration timestamp.
     * @return bool
     */
    public function storeToken($token, $user_id, $token_type, $expires_at)
    {
        global $wpdb;

        $table = $wpdb->prefix . WP_API_CODEIA_TOKENS_TABLE;
        $token_id = $this->getTokenId($token);

        $data = array(
            'token_id' => $token_id,
            'user_id' => $user_id,
            'token_type' => $token_type,
            'expires_at' => date('Y-m-d H:i:s', $expires_at),
            'created_at' => current_time('mysql'),
        );

        $replace = $wpdb->replace($table, $data);

        if ($replace === false) {
            $this->logger->error('Failed to store token', array(
                'user_id' => $user_id,
                'token_type' => $token_type,
            ));
            return false;
        }

        return true;
    }

    /**
     * Get token ID from JWT.
     *
     * @since 1.0.0
     *
     * @param string $token JWT token.
     * @return string Token ID (JTI).
     */
    protected function getTokenId($token)
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            return wp_hash($token);
        }

        $payload = json_decode($this->base64UrlDecode($segments[1]), true);

        return isset($payload['jti']) ? $payload['jti'] : wp_hash($token);
    }

    /**
     * Blacklist a token.
     *
     * @since 1.0.0
     *
     * @param string $token Token to blacklist.
     * @return bool
     */
    public function blacklist($token)
    {
        $token_id = $this->getTokenId($token);
        $blacklist = $this->getBlacklist();

        $blacklist[$token_id] = array(
            'blacklisted_at' => time(),
            'expires_at' => time() + WP_API_CODEIA_JWT_REFRESH_TTL,
        );

        $this->saveBlacklist($blacklist);

        return true;
    }

    /**
     * Check if token is blacklisted.
     *
     * @since 1.0.0
     *
     * @param string $token Token to check.
     * @return bool
     */
    public function isBlacklisted($token)
    {
        $token_id = $this->getTokenId($token);
        $blacklist = $this->getBlacklist();

        if (!isset($blacklist[$token_id])) {
            return false;
        }

        // Clean up expired blacklist entries
        if (time() > $blacklist[$token_id]['expires_at']) {
            unset($blacklist[$token_id]);
            $this->saveBlacklist($blacklist);
            return false;
        }

        return true;
    }

    /**
     * Get token blacklist.
     *
     * @since 1.0.0
     *
     * @return array
     */
    protected function getBlacklist()
    {
        $blacklist = get_option($this->blacklistCacheKey, array());
        return is_array($blacklist) ? $blacklist : array();
    }

    /**
     * Save token blacklist.
     *
     * @since 1.0.0
     *
     * @param array $blacklist Blacklist data.
     * @return bool
     */
    protected function saveBlacklist($blacklist)
    {
        return update_option($this->blacklistCacheKey, $blacklist, false);
    }

    /**
     * Revoke all tokens for a user.
     *
     * @since 1.0.0
     *
     * @param int $user_id User ID.
     * @return bool
     */
    public function revokeUserTokens($user_id)
    {
        global $wpdb;

        $table = $wpdb->prefix . WP_API_CODEIA_TOKENS_TABLE;

        // Get all user tokens
        $tokens = $wpdb->get_col($wpdb->prepare(
            "SELECT token_id FROM {$table} WHERE user_id = %d",
            $user_id
        ));

        if (empty($tokens)) {
            return true;
        }

        // Add to blacklist
        $blacklist = $this->getBlacklist();

        foreach ($tokens as $token_id) {
            $blacklist[$token_id] = array(
                'blacklisted_at' => time(),
                'expires_at' => time() + WP_API_CODEIA_JWT_REFRESH_TTL,
            );
        }

        $this->saveBlacklist($blacklist);

        // Delete from database
        $deleted = $wpdb->delete(
            $table,
            array('user_id' => $user_id),
            array('%d')
        );

        $this->logger->info('Revoked tokens for user', array(
            'user_id' => $user_id,
            'count' => count($tokens),
        ));

        return $deleted !== false;
    }

    /**
     * Clean up expired tokens.
     *
     * @since 1.0.0
     *
     * @return int Number of tokens deleted.
     */
    public function cleanupExpiredTokens()
    {
        global $wpdb;

        $table = $wpdb->prefix . WP_API_CODEIA_TOKENS_TABLE;

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE expires_at < %s",
            current_time('mysql')
        ));

        // Also clean up blacklist
        $blacklist = $this->getBlacklist();
        $now = time();
        $updated = false;

        foreach ($blacklist as $token_id => $data) {
            if ($now > $data['expires_at']) {
                unset($blacklist[$token_id]);
                $updated = true;
            }
        }

        if ($updated) {
            $this->saveBlacklist($blacklist);
        }

        return $deleted;
    }
}
