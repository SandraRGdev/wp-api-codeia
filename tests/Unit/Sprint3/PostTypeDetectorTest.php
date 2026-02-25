<?php
/**
 * Post Type Detector Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Schema\PostTypeDetector;
use WP_API_Codeia\Utils\Cache\CacheManager;
use WP_API_Codeia\Utils\Logger\Logger;

/**
 * Test Post Type Detector class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Schema\PostTypeDetector
 */
class PostTypeDetectorTest extends TestCase
{
    /**
     * Post Type Detector instance.
     *
     * @var PostTypeDetector
     */
    protected $detector;

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
        $this->cache = $this->createMock(CacheManager::class);
        $this->logger = $this->createMock(Logger::class);
        $this->detector = new PostTypeDetector($this->cache, $this->logger);
    }

    /**
     * Test can detect post types.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanDetectPostTypes()
    {
        // Test with mock cache returning null
        $this->cache->method('get')->willReturn(null);

        $postTypes = $this->detector->detect();

        $this->assertIsArray($postTypes);
        $this->assertArrayHasKey('post', $postTypes);
        $this->assertArrayHasKey('page', $postTypes);
    }

    /**
     * Test post info contains required keys.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testPostInfoContainsRequiredKeys()
    {
        $info = $this->detector->getInfo('post');

        $this->assertIsArray($info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('label', $info);
        $this->assertArrayHasKey('public', $info);
        $this->assertArrayHasKey('hierarchical', $info);
        $this->assertArrayHasKey('supports', $info);
        $this->assertArrayHasKey('taxonomies', $info);
    }

    /**
     * Test internal post types are excluded.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testInternalPostTypesAreExcluded()
    {
        $postTypes = $this->detector->detect();

        $this->assertArrayNotHasKey('revision', $postTypes);
        $this->assertArrayNotHasKey('nav_menu_item', $postTypes);
        $this->assertArrayNotHasKey('attachment', $postTypes);
    }

    /**
     * Test get supported post types.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetSupportedPostTypes()
    {
        $supported = $this->detector->getSupportedPostTypes();

        $this->assertIsArray($supported);
        $this->assertArrayHasKey('post', $supported);
    }

    /**
     * Test is supported for valid post type.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testIsSupportedForValidPostType()
    {
        $this->assertTrue($this->detector->isSupported('post'));
        $this->assertTrue($this->detector->isSupported('page'));
    }

    /**
     * Test is not supported for invalid post type.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function test IsNotSupportedForInvalidPostType()
    {
        $this->assertFalse($this->detector->isSupported('invalid_post_type'));
    }

    /**
     * Test get capabilities for post type.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetCapabilitiesForPostType()
    {
        $capabilities = $this->detector->getCapabilities('post');

        $this->assertIsArray($capabilities);
        $this->assertArrayHasKey('edit_post', $capabilities);
        $this->assertArrayHasKey('read_post', $capabilities);
        $this->assertArrayHasKey('delete_post', $capabilities);
        $this->assertArrayHasKey('edit_posts', $capabilities);
        $this->assertArrayHasKey('publish_posts', $capabilities);
    }

    /**
     * Test post type has supports.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testPostTypeHasSupports()
    {
        $info = $this->detector->getInfo('post');

        $this->assertIsArray($info['supports']);
        $this->assertContains('title', $info['supports']);
        $this->assertContains('editor', $info['supports']);
    }

    /**
     * Test page is hierarchical.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testPageIsHierarchical()
    {
        $info = $this->detector->getInfo('page');

        $this->assertTrue($info['hierarchical']);
    }

    /**
     * Test post is not hierarchical.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testPostIsNotHierarchical()
    {
        $info = $this->detector->getInfo('post');

        $this->assertFalse($info['hierarchical']);
    }
}
