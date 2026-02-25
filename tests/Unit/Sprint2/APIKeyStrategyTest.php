<?php
/**
 * API Key Strategy Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Auth\Strategies\APIKeyStrategy;
use WP_API_Codeia\Utils\Logger\Logger;

/**
 * Test API Key Strategy class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Auth\Strategies\APIKeyStrategy
 */
class APIKeyStrategyTest extends TestCase
{
    /**
     * API Key Strategy instance.
     *
     * @var APIKeyStrategy
     */
    protected $apiKeyStrategy;

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
        $this->apiKeyStrategy = new APIKeyStrategy($this->logger);
    }

    /**
     * Test supports API key credentials.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testSupportsAPIKeyCredentials()
    {
        $credentials = array(
            'type' => WP_API_CODEIA_AUTH_API_KEY,
            'api_key' => 'wack_1_1_test123_checksum',
        );

        $this->assertTrue($this->apiKeyStrategy->supports($credentials));
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
            'type' => 'jwt',
            'token' => 'test-token',
        );

        $this->assertFalse($this->apiKeyStrategy->supports($credentials));
    }

    /**
     * Test validates API key format.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testValidatesAPIKeyFormat()
    {
        $validKey = 'wack_1_1_' . wp_generate_password(32, false) . '_abcd1234';
        $invalidKey = 'invalid_key_format';

        $this->assertTrue($this->apiKeyStrategy->isValidFormat($validKey));
        $this->assertFalse($this->apiKeyStrategy->isValidFormat($invalidKey));
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
        $challenge = $this->apiKeyStrategy->getChallenge();

        $this->assertIsString($challenge);
        $this->assertStringContainsString('Key', $challenge);
    }

    /**
     * Test generate API key.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGenerateAPIKey()
    {
        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->apiKeyStrategy);
        $method = $reflection->getMethod('calculateChecksum');
        $method->setAccessible(true);

        $checksum = $method->invoke($this->apiKeyStrategy, 1, 1, 'randomstring');

        $this->assertIsString($checksum);
        $this->assertNotEmpty($checksum);
    }

    /**
     * Test API key prefix constant.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testAPIKeyPrefixConstant()
    {
        $prefix = WP_API_CODEIA_API_KEY_PREFIX;

        $this->assertEquals('wack', $prefix);
    }
}
