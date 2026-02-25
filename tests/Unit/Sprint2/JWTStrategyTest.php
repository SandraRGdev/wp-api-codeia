<?php
/**
 * JWT Strategy Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Auth\Strategies\JWTStrategy;
use WP_API_Codeia\Auth\Token\TokenManager;
use WP_API_Codeia\Utils\Logger\Logger;

/**
 * Test JWT Strategy class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Auth\Strategies\JWTStrategy
 */
class JWTStrategyTest extends TestCase
{
    /**
     * JWT Strategy instance.
     *
     * @var JWTStrategy
     */
    protected $jwtStrategy;

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
        $this->tokenManager = $this->createMock(TokenManager::class);
        $this->jwtStrategy = new JWTStrategy($this->tokenManager, $this->logger);
    }

    /**
     * Test supports JWT credentials.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testSupportsJWTCredentials()
    {
        $credentials = array(
            'type' => WP_API_CODEIA_AUTH_JWT,
            'token' => 'test-token',
        );

        $this->assertTrue($this->jwtStrategy->supports($credentials));
    }

    /**
     * Test does not support other credential types.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testDoesNotSupportOtherCredentialTypes()
    {
        $credentials = array(
            'type' => 'api_key',
            'api_key' => 'test-key',
        );

        $this->assertFalse($this->jwtStrategy->supports($credentials));
    }

    /**
     * Test returns challenge string.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testReturnsChallengeString()
    {
        $challenge = $this->jwtStrategy->getChallenge();

        $this->assertIsString($challenge);
        $this->assertStringContainsString('Bearer', $challenge);
    }

    /**
     * Test generate tokens returns array with access and refresh.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGenerateTokensReturnsArrayWithAccessAndRefresh()
    {
        $user = $this->createMock(\WP_User::class);
        $user->ID = 1;

        $this->tokenManager->method('issue')->willReturn('test.jwt.token');
        $this->tokenManager->method('validate')->willReturn(array('sub' => '1', 'type' => 'access'));

        $tokens = $this->jwtStrategy->generateTokens($user);

        $this->assertArrayHasKey('access_token', $tokens);
        $this->assertArrayHasKey('refresh_token', $tokens);
        $this->assertArrayHasKey('token_type', $tokens);
        $this->assertEquals('Bearer', $tokens['token_type']);
    }

    /**
     * Test get token manager.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetTokenManager()
    {
        $manager = $this->jwtStrategy->getTokenManager();

        $this->assertSame($this->tokenManager, $manager);
    }

    /**
     * Test revoke user tokens.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testRevokeUserTokens()
    {
        $user = $this->createMock(\WP_User::class);
        $user->ID = 1;

        $this->tokenManager->expects($this->once())
            ->method('revokeUserTokens')
            ->with(1)
            ->willReturn(true);

        $result = $this->jwtStrategy->revoke($user);

        $this->assertTrue($result);
    }
}
