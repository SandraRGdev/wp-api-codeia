<?php
/**
 * Token Manager Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Auth\Token\TokenManager;
use WP_API_Codeia\Utils\Logger\Logger;

/**
 * Test Token Manager class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Auth\Token\TokenManager
 */
class TokenManagerTest extends TestCase
{
    /**
     * Token Manager instance.
     *
     * @var TokenManager
     */
    protected $tokenManager;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Set up test environment.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->tokenManager = new TokenManager($this->logger);
    }

    /**
     * Test can issue a token.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanIssueToken()
    {
        $payload = array(
            'sub' => '123',
            'name' => 'Test User',
            'exp' => time() + 3600,
        );

        $token = $this->tokenManager->issue($payload);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $token);
    }

    /**
     * Test can validate a valid token.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanValidateValidToken()
    {
        $payload = array(
            'iss' => 'https://example.com',
            'aud' => 'wp-custom-api',
            'iat' => time(),
            'exp' => time() + 3600,
            'sub' => '123',
            'type' => 'access',
            'jti' => 'test-token-id-123',
        );

        $token = $this->tokenManager->issue($payload);
        $result = $this->tokenManager->validate($token);

        $this->assertIsArray($result);
        $this->assertEquals('123', $result['sub']);
        $this->assertEquals('access', $result['type']);
    }

    /**
     * Test validation fails for invalid format.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testValidationFailsForInvalidFormat()
    {
        $result = $this->tokenManager->validate('invalid-token');

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals(WP_API_CODEIA_ERROR_AUTH_INVALID, $result->get_error_code());
    }

    /**
     * Test validation fails for expired token.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testValidationFailsForExpiredToken()
    {
        $payload = array(
            'iss' => 'https://example.com',
            'aud' => 'wp-custom-api',
            'iat' => time() - 7200,
            'exp' => time() - 3600,
            'sub' => '123',
            'type' => 'access',
            'jti' => 'test-token-id-123',
        );

        $token = $this->tokenManager->issue($payload);
        $result = $this->tokenManager->validate($token);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals(WP_API_CODEIA_ERROR_AUTH_EXPIRED, $result->get_error_code());
    }

    /**
     * Test can blacklist a token.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanBlacklistToken()
    {
        $payload = array(
            'sub' => '123',
            'exp' => time() + 3600,
            'jti' => 'test-token-blacklist',
        );

        $token = $this->tokenManager->issue($payload);

        $result = $this->tokenManager->blacklist($token);

        $this->assertTrue($result);
        $this->assertTrue($this->tokenManager->isBlacklisted($token));
    }

    /**
     * Test can check if token is blacklisted.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanCheckIfTokenIsBlacklisted()
    {
        $payload = array(
            'sub' => '123',
            'exp' => time() + 3600,
            'jti' => 'test-token-check-blacklist',
        );

        $token = $this->tokenManager->issue($payload);

        $this->assertFalse($this->tokenManager->isBlacklisted($token));

        $this->tokenManager->blacklist($token);

        $this->assertTrue($this->tokenManager->isBlacklisted($token));
    }

    /**
     * Test base64 URL encoding.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testBase64UrlEncoding()
    {
        $data = 'test data with special chars: +/= ';

        $encoded = $this->tokenManager->issue(array('test' => $data));

        $this->assertStringNotContainsString('+', $encoded);
        $this->assertStringNotContainsString('/', $encoded);
        $this->assertStringNotContainsString('=', $encoded);
    }

    /**
     * Test token ID generation.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testTokenIdGeneration()
    {
        $payload = array(
            'sub' => '123',
            'exp' => time() + 3600,
            'jti' => 'test-token-id-specific',
        );

        $token = $this->tokenManager->issue($payload);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($this->tokenManager);
        $method = $reflection->getMethod('getTokenId');
        $method->setAccessible(true);

        $tokenId = $method->invoke($this->tokenManager, $token);

        $this->assertEquals('test-token-id-specific', $tokenId);
    }
}
