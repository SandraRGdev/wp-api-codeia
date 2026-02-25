<?php
/**
 * JWT Authentication Strategy
 *
 * @package WP_API_Codeia
 */

namespace WP_API_Codeia\Auth\Strategies;

use WP_API_Codeia\Auth\Token\TokenManager;
use WP_API_Codeia\Utils\Logger\Logger;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * JWT Authentication Strategy.
 *
 * Implements JWT (JSON Web Token) authentication using RS256 algorithm.
 *
 * @since 1.0.0
 */
class JWTStrategy implements AuthStrategyInterface
{
    /**
     * Token Manager instance.
     *
     * @since 1.0.0
     *
     * @var TokenManager
     */
    protected $tokenManager;

    /**
     * Logger instance.
     *
     * @since 1.0.0
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Create a new JWT Strategy instance.
     *
     * @since 1.0.0
     *
     * @param TokenManager $tokenManager Token Manager.
     * @param Logger       $logger       Logger instance.
     */
    public function __construct(TokenManager $tokenManager, Logger $logger)
    {
        $this->tokenManager = $tokenManager;
        $this->logger = $logger;
    }

    /**
     * Check if strategy supports the credentials.
     *
     * @since 1.0.0
     *
     * @param array $credentials Credentials array.
     * @return bool
     */
    public function supports(array $credentials)
    {
        return isset($credentials['type']) && $credentials['type'] === WP_API_CODEIA_AUTH_JWT
            && isset($credentials['token']);
    }

    /**
     * Authenticate user with JWT token.
     *
     * @since 1.0.0
     *
     * @param array $credentials Credentials array with 'token' key.
     * @return \WP_User|\WP_Error
     */
    public function authenticate(array $credentials)
    {
        $token = $credentials['token'];

        if (empty($token)) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_MISSING,
                'Token not provided',
                array('status' => 401)
            );
        }

        // Validate token
        $payload = $this->tokenManager->validate($token);

        if (is_wp_error($payload)) {
            return $payload;
        }

        // Get user from payload
        $user_id = isset($payload['sub']) ? intval($payload['sub']) : 0;

        if ($user_id <= 0) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid token payload',
                array('status' => 401)
            );
        }

        $user = get_userdata($user_id);

        if (!$user) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'User not found',
                array('status' => 401)
            );
        }

        // Check if token is blacklisted
        if ($this->tokenManager->isBlacklisted($token)) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_EXPIRED,
                'Token has been revoked',
                array('status' => 401)
            );
        }

        // Verify token type
        $tokenType = isset($payload['type']) ? $payload['type'] : '';

        if ($tokenType !== WP_API_CODEIA_TOKEN_ACCESS) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid token type',
                array('status' => 401)
            );
        }

        return $user;
    }

    /**
     * Generate access and refresh tokens for user.
     *
     * @since 1.0.0
     *
     * @param \WP_User $user User object.
     * @return array Array with access_token and refresh_token.
     */
    public function generateTokens($user)
    {
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user);

        return array(
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => WP_API_CODEIA_JWT_ACCESS_TTL,
        );
    }

    /**
     * Generate access token.
     *
     * @since 1.0.0
     *
     * @param \WP_User $user User object.
     * @return string JWT access token.
     */
    protected function generateAccessToken($user)
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + WP_API_CODEIA_JWT_ACCESS_TTL;

        $payload = array(
            'iss' => $this->getIssuer(),
            'aud' => $this->getAudience(),
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => (string) $user->ID,
            'type' => WP_API_CODEIA_TOKEN_ACCESS,
            'user' => array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'email' => $user->user_email,
                'roles' => $user->roles,
            ),
            'jti' => $this->generateJTI(),
        );

        $token = $this->tokenManager->issue($payload);

        // Store token in database
        $this->tokenManager->storeToken($token, $user->ID, WP_API_CODEIA_TOKEN_ACCESS, $expiresAt);

        return $token;
    }

    /**
     * Generate refresh token.
     *
     * @since 1.0.0
     *
     * @param \WP_User $user User object.
     * @return string JWT refresh token.
     */
    protected function generateRefreshToken($user)
    {
        $issuedAt = time();
        $expiresAt = $issuedAt + WP_API_CODEIA_JWT_REFRESH_TTL;

        $payload = array(
            'iss' => $this->getIssuer(),
            'aud' => $this->getAudience(),
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => (string) $user->ID,
            'type' => WP_API_CODEIA_TOKEN_REFRESH,
            'jti' => $this->generateJTI(),
        );

        $token = $this->tokenManager->issue($payload);

        // Store token in database
        $this->tokenManager->storeToken($token, $user->ID, WP_API_CODEIA_TOKEN_REFRESH, $expiresAt);

        return $token;
    }

    /**
     * Refresh access token using refresh token.
     *
     * @since 1.0.0
     *
     * @param string $refresh_token Refresh token.
     * @return array|\WP_Error New tokens or error.
     */
    public function refresh($refresh_token)
    {
        // Validate refresh token
        $payload = $this->tokenManager->validate($refresh_token);

        if (is_wp_error($payload)) {
            return $payload;
        }

        // Verify token type
        $tokenType = isset($payload['type']) ? $payload['type'] : '';

        if ($tokenType !== WP_API_CODEIA_TOKEN_REFRESH) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid refresh token',
                array('status' => 401)
            );
        }

        // Get user
        $user_id = isset($payload['sub']) ? intval($payload['sub']) : 0;

        if ($user_id <= 0) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'Invalid token payload',
                array('status' => 401)
            );
        }

        $user = get_userdata($user_id);

        if (!$user) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_INVALID,
                'User not found',
                array('status' => 401)
            );
        }

        // Check if refresh token is blacklisted
        if ($this->tokenManager->isBlacklisted($refresh_token)) {
            return new \WP_Error(
                WP_API_CODEIA_ERROR_AUTH_EXPIRED,
                'Refresh token has been revoked',
                array('status' => 401)
            );
        }

        // Blacklist old refresh token (one-time use)
        $this->tokenManager->blacklist($refresh_token);

        // Generate new tokens
        return $this->generateTokens($user);
    }

    /**
     * Revoke tokens for user.
     *
     * @since 1.0.0
     *
     * @param \WP_User $user User object.
     * @return bool True on success.
     */
    public function revoke($user)
    {
        return $this->tokenManager->revokeUserTokens($user->ID);
    }

    /**
     * Get WWW-Authenticate challenge.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function getChallenge()
    {
        return 'Bearer realm="' . $this->getIssuer() . '"';
    }

    /**
     * Get token issuer.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected function getIssuer()
    {
        return get_bloginfo('url');
    }

    /**
     * Get token audience.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected function getAudience()
    {
        return WP_API_CODEIA_API_NAMESPACE;
    }

    /**
     * Generate JWT ID.
     *
     * @since 1.0.0
     *
     * @return string
     */
    protected function generateJTI()
    {
        return wp_generate_password(32, false);
    }

    /**
     * Get token manager.
     *
     * @since 1.0.0
     *
     * @return TokenManager
     */
    public function getTokenManager()
    {
        return $this->tokenManager;
    }
}
