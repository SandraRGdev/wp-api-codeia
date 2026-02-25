<?php
/**
 * Rate Limiter Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Permissions\RateLimiter;
use WP_API_Codeia\Utils\Cache\CacheManager;
use WP_API_Codeia\Utils\Logger\Logger;

/**
 * Test Rate Limiter class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Permissions\RateLimiter
 */
class RateLimiterTest extends TestCase
{
    /**
     * Rate Limiter instance.
     *
     * @var RateLimiter
     */
    protected $limiter;

    /**
     * Cache Manager instance.
     *
     * @var CacheManager
     */
    protected $cache;

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
        $this->cache = new CacheManager(new \WP_API_Codeia\Core\Config());
        $this->logger = $this->createMock(Logger::class);
        $this->limiter = new RateLimiter($this->cache, $this->logger);
    }

    /**
     * Test first request passes rate limit.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFirstRequestPassesRateLimit()
    {
        $result = $this->limiter->check(1, 10, 60);

        $this->assertTrue($result);
    }

    /**
     * Test requests within limit pass.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testRequestsWithinLimitPass()
    {
        for ($i = 0; $i < 5; $i++) {
            $result = $this->limiter->check(1, 10, 60);
            $this->assertTrue($result);
        }
    }

    /**
     * Test requests exceeding limit fail.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testRequestsExceedingLimitFail()
    {
        // Set a low limit for testing
        $limit = 3;

        for ($i = 0; $i < $limit; $i++) {
            $this->limiter->check(1, $limit, 60);
        }

        // Next request should fail
        $result = $this->limiter->check(1, $limit, 60);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals(WP_API_CODEIA_ERROR_RATE_LIMITED, $result->get_error_code());
    }

    /**
     * Test get remaining requests.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetRemainingRequests()
    {
        $limit = 10;
        $window = 60;

        // Make 3 requests
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->check(1, $limit, $window);
        }

        $remaining = $this->limiter->getRemaining(1, $limit, $window);

        $this->assertEquals(7, $remaining['remaining']);
        $this->assertArrayHasKey('reset', $remaining);
    }

    /**
     * Test reset rate limit.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testResetRateLimit()
    {
        $limit = 3;

        // Use up the limit
        for ($i = 0; $i < $limit; $i++) {
            $this->limiter->check(1, $limit, 60);
        }

        // Should be rate limited
        $result = $this->limiter->check(1, $limit, 60);
        $this->assertInstanceOf(\WP_Error::class, $result);

        // Reset
        $this->limiter->reset(1);

        // Should work again
        $result = $this->limiter->check(1, $limit, 60);
        $this->assertTrue($result);
    }

    /**
     * Test get rate limit headers.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetRateLimitHeaders()
    {
        $headers = $this->limiter->getHeaders(1, 100, 3600);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);

        $this->assertEquals(100, $headers['X-RateLimit-Limit']);
        $this->assertEquals(100, $headers['X-RateLimit-Remaining']);
    }

    /**
     * Test different users have separate limits.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testDifferentUsersHaveSeparateLimits()
    {
        $limit = 2;

        // User 1 uses up their limit
        for ($i = 0; $i < $limit; $i++) {
            $this->limiter->check(1, $limit, 60);
        }

        // User 1 should be rate limited
        $result1 = $this->limiter->check(1, $limit, 60);
        $this->assertInstanceOf(\WP_Error::class, $result1);

        // User 2 should still work
        $result2 = $this->limiter->check(2, $limit, 60);
        $this->assertTrue($result2);
    }

    /**
     * Test check by IP address.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCheckByIPAddress()
    {
        $ip = '192.168.1.1';
        $limit = 2;

        $result1 = $this->limiter->checkByIP($ip, $limit, 60);
        $result2 = $this->limiter->checkByIP($ip, $limit, 60);

        $this->assertTrue($result1);
        $this->assertTrue($result2);

        // Third request should fail
        $result3 = $this->limiter->checkByIP($ip, $limit, 60);
        $this->assertInstanceOf(\WP_Error::class, $result3);
    }

    /**
     * Test check by auth method.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCheckByAuthMethod()
    {
        $method = 'jwt';
        $limit = 2;

        $result1 = $this->limiter->checkByAuthMethod($method, $limit, 60);
        $result2 = $this->limiter->checkByAuthMethod($method, $limit, 60);

        $this->assertTrue($result1);
        $this->assertTrue($result2);

        // Third request should fail
        $result3 = $this->limiter->checkByAuthMethod($method, $limit, 60);
        $this->assertInstanceOf(\WP_Error::class, $result3);
    }
}
