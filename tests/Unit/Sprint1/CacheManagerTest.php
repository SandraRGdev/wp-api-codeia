<?php
/**
 * Cache Manager Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Utils\Cache\CacheManager;

/**
 * Test Cache Manager class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Utils\Cache\CacheManager
 */
class CacheManagerTest extends TestCase
{
    /**
     * Cache Manager instance.
     *
     * @var CacheManager
     */
    protected $cache;

    /**
     * Set up test environment.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cache = new CacheManager();
        $this->cache->setEnabled(true);
    }

    /**
     * Tear down test environment.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->cache->clear();
    }

    /**
     * Test cache can be enabled/disabled.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testSetEnabled()
    {
        $this->cache->setEnabled(false);
        $this->assertFalse($this->cache->isEnabled());

        $this->cache->setEnabled(true);
        $this->assertTrue($cache->isEnabled());
    }

    /**
     * Test cache set and get operations.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testSetAndGet()
    {
        $key = 'test_key';
        $value = 'test_value';

        $result = $this->cache->set($key, $value, 60);
        $this->assertTrue($result);

        $retrieved = $this->cache->get($key);
        $this->assertEquals($value, $retrieved);
    }

    /**
     * Test cache returns default when key not found.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetReturnsDefault()
    {
        $result = $this->cache->get('non_existent_key', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    /**
     * Test cache delete operation.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testDelete()
    {
        $key = 'test_delete_key';
        $this->cache->set($key, 'value', 60);

        $result = $this->cache->delete($key);
        $this->assertTrue($result);

        $retrieved = $this->cache->get($key);
        $this->assertNull($retrieved);
    }

    /**
     * Test remember method caches callback result.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testRememberCachesCallbackResult()
    {
        $key = 'test_remember_key';
        $callbackCount = 0;

        $callback = function () use (&$callbackCount) {
            $callbackCount++;
            return 'callback_result';
        };

        // First call executes callback
        $result1 = $this->cache->remember($key, $callback);
        $this->assertEquals('callback_result', $result1);
        $this->assertEquals(1, $callbackCount);

        // Second call uses cached value
        $result2 = $this->cache->remember($key, $callback);
        $this->assertEquals('callback_result', $result2);
        $this->assertEquals(1, $callbackCount); // Callback not executed again
    }

    /**
     * Test clear operation clears cache.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testClear()
    {
        $this->cache->set('key1', 'value1', 60);
        $this->cache->set('key2', 'value2', 60);

        $this->cache->clear();

        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
    }

    /**
     * Test cache driver detection.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testDriverDetection()
    {
        $driver = $this->cache->getDriver();

        $this->assertIsString($driver);
        $this->assertContains($driver, array('object-cache', 'transient', 'auto'));
    }

    /**
     * Test hash generation.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGenerateHash()
    {
        $data = array('key1' => 'value1', 'key2' => 'value2');
        $hash1 = $this->cache->generateHash($data);

        $this->assertIsString($hash1);
        $this->assertEquals(32, strlen($hash1)); // MD5 hash

        // Same data produces same hash
        $hash2 = $this->cache->generateHash($data);
        $this->assertEquals($hash1, $hash2);
    }

    /**
     * Test default TTL by key type.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetDefaultTtlByKeyType()
    {
        // Use reflection to access protected method
        $method = new \ReflectionMethod($this->cache, 'getDefaultTtl');
        $method->setAccessible(true);

        // Schema keys should have 3600s TTL
        $schemaTtl = $method->invoke($this->cache, 'schema:some_key');
        $this->assertEquals(3600, $schemaTtl);

        // Permission keys should have 900s TTL
        $permTtl = $method->invoke($this->cache, 'permissions:some_key');
        $this->assertEquals(900, $permTtl);

        // Data keys should have 300s TTL
        $dataTtl = $method->invoke($this->cache, 'data:some_key');
        $this->assertEquals(300, $dataTtl);
    }

    /**
     * Test cache key building.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testKeyBuilding()
    {
        $method = new \ReflectionMethod($this->cache, 'buildKey');
        $method->setAccessible(true);

        $fullKey = $method->invoke($this->cache, 'my_key');

        $this->assertStringContainsString('wp_api_codeia:my_key', $fullKey);
    }
}
