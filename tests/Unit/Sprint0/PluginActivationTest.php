<?php
/**
 * Plugin Activation Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test plugin activation.
 *
 * @since 1.0.0
 *
 * @covers \wp_api_codeia_activate
 */
class PluginActivationTest extends TestCase
{
    /**
     * Test plugin constants are defined.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testPluginConstantsAreDefined()
    {
        $this->assertTrue(defined('WP_API_CODEIA_VERSION'));
        $this->assertTrue(defined('WP_API_CODEIA_PLUGIN_FILE'));
        $this->assertTrue(defined('WP_API_CODEIA_PLUGIN_DIR'));
        $this->assertTrue(defined('WP_API_CODEIA_API_NAMESPACE'));
        $this->assertTrue(defined('WP_API_CODEIA_API_VERSION'));
        $this->assertTrue(defined('WP_API_CODEIA_REST_URL'));
    }

    /**
     * Test plugin version format.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testPluginVersionFormat()
    {
        $version = WP_API_CODEIA_VERSION;
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+(-[a-z0-9]+)?$/', $version);
    }

    /**
     * Test API namespace is not empty.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testApiNamespaceIsNotEmpty()
    {
        $this->assertNotEmpty(WP_API_CODEIA_API_NAMESPACE);
        $this->assertEquals('wp-custom-api', WP_API_CODEIA_API_NAMESPACE);
    }

    /**
     * Test API version format.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testApiVersionFormat()
    {
        $version = WP_API_CODEIA_API_VERSION;
        $this->assertMatchesRegularExpression('/^v\d+$/', $version);
    }

    /**
     * Test error codes are defined.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testErrorCodesAreDefined()
    {
        $this->assertTrue(defined('WP_API_CODEIA_ERROR_AUTH_MISSING'));
        $this->assertTrue(defined('WP_API_CODEIA_ERROR_AUTH_INVALID'));
        $this->assertTrue(defined('WP_API_CODEIA_ERROR_AUTH_EXPIRED'));
        $this->assertTrue(defined('WP_API_CODEIA_ERROR_FORBIDDEN'));
        $this->assertTrue(defined('WP_API_CODEIA_ERROR_VALIDATION_FAILED'));
        $this->assertTrue(defined('WP_API_CODEIA_ERROR_NOT_FOUND'));
        $this->assertTrue(defined('WP_API_CODEIA_ERROR_RATE_LIMITED'));
    }

    /**
     * Test authentication constants are defined.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testAuthConstantsAreDefined()
    {
        $this->assertTrue(defined('WP_API_CODEIA_AUTH_JWT'));
        $this->assertTrue(defined('WP_API_CODEIA_AUTH_API_KEY'));
        $this->assertTrue(defined('WP_API_CODEIA_AUTH_APP_PASSWORD'));
        $this->assertTrue(defined('WP_API_CODEIA_TOKEN_ACCESS'));
        $this->assertTrue(defined('WP_API_CODEIA_TOKEN_REFRESH'));
    }

    /**
     * Test JWT constants have correct values.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testJwtConstantsAreCorrect()
    {
        $this->assertEquals('jwt', WP_API_CODEIA_AUTH_JWT);
        $this->assertEquals('access', WP_API_CODEIA_TOKEN_ACCESS);
        $this->assertEquals('refresh', WP_API_CODEIA_TOKEN_REFRESH);
        $this->assertEquals('RS256', WP_API_CODEIA_JWT_ALGORITHM);
        $this->assertEquals(3600, WP_API_CODEIA_JWT_ACCESS_TTL);
        $this->assertEquals(2592000, WP_API_CODEIA_JWT_REFRESH_TTL);
    }

    /**
     * Test API key prefix constant.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testApiKeyPrefixConstant()
    {
        $this->assertTrue(defined('WP_API_CODEIA_API_KEY_PREFIX'));
        $this->assertEquals('wack', WP_API_CODEIA_API_KEY_PREFIX);
    }

    /**
     * Test database table name constants.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testDatabaseTableConstants()
    {
        $this->assertTrue(defined('WP_API_CODEIA_TOKENS_TABLE'));
        $this->assertTrue(defined('WP_API_CODEIA_API_KEYS_TABLE'));
        $this->assertEquals('codeia_tokens', WP_API_CODEIA_TOKENS_TABLE);
        $this->assertEquals('codeia_api_keys', WP_API_CODEIA_API_KEYS_TABLE);
    }
}
