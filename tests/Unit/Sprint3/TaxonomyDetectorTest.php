<?php
/**
 * Taxonomy Detector Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Schema\TaxonomyDetector;
use WP_API_Codeia\Utils\Cache\CacheManager;
use WP_API_Codeia\Utils\Logger\Logger;

/**
 * Test Taxonomy Detector class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Schema\TaxonomyDetector
 */
class TaxonomyDetectorTest extends TestCase
{
    /**
     * Taxonomy Detector instance.
     *
     * @var TaxonomyDetector
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
        $this->detector = new TaxonomyDetector($this->cache, $this->logger);
    }

    /**
     * Test can detect taxonomies.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanDetectTaxonomies()
    {
        $taxonomies = $this->detector->detect();

        $this->assertIsArray($taxonomies);
        $this->assertArrayHasKey('category', $taxonomies);
        $this->assertArrayHasKey('post_tag', $taxonomies);
    }

    /**
     * Test taxonomy info contains required keys.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testTaxonomyInfoContainsRequiredKeys()
    {
        $info = $this->detector->getInfo('category');

        $this->assertIsArray($info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('label', $info);
        $this->assertArrayHasKey('public', $info);
        $this->assertArrayHasKey('hierarchical', $info);
        $this->assertArrayHasKey('object_type', $info);
        $this->assertArrayHasKey('capabilities', $info);
    }

    /**
     * Test category is hierarchical.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCategoryIsHierarchical()
    {
        $info = $this->detector->getInfo('category');

        $this->assertTrue($info['hierarchical']);
    }

    /**
     * Test post tag is not hierarchical.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testPostTagIsNotHierarchical()
    {
        $info = $this->detector->getInfo('post_tag');

        $this->assertFalse($info['hierarchical']);
    }

    /**
     * Test internal taxonomies are excluded.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testInternalTaxonomiesAreExcluded()
    {
        $taxonomies = $this->detector->detect();

        $this->assertArrayNotHasKey('nav_menu', $taxonomies);
        $this->assertArrayNotHasKey('link_category', $taxonomies);
        $this->assertArrayNotHasKey('post_format', $taxonomies);
    }

    /**
     * Test get taxonomies for post type.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetTaxonomiesForPostType()
    {
        $taxonomies = $this->detector->getForPostType('post');

        $this->assertIsArray($taxonomies);
        $this->assertArrayHasKey('category', $taxonomies);
        $this->assertArrayHasKey('post_tag', $taxonomies);
    }

    /**
     * Test get taxonomies for page post type.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetTaxonomiesForPagePostType()
    {
        $taxonomies = $this->detector->getForPostType('page');

        $this->assertIsArray($taxonomies);
        // Pages typically don't have tags
        $this->assertArrayNotHasKey('post_tag', $taxonomies);
    }

    /**
     * Test is supported for valid taxonomy.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testIsSupportedForValidTaxonomy()
    {
        $this->assertTrue($this->detector->isSupported('category'));
        $this->assertTrue($this->detector->isSupported('post_tag'));
    }

    /**
     * Test is not supported for invalid taxonomy.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testIsNotSupportedForInvalidTaxonomy()
    {
        $this->assertFalse($this->detector->isSupported('invalid_taxonomy'));
    }

    /**
     * Test get capabilities for taxonomy.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetCapabilitiesForTaxonomy()
    {
        $capabilities = $this->detector->getCapabilities('category');

        $this->assertIsArray($capabilities);
        $this->assertArrayHasKey('manage_terms', $capabilities);
        $this->assertArrayHasKey('edit_terms', $capabilities);
        $this->assertArrayHasKey('delete_terms', $capabilities);
        $this->assertArrayHasKey('assign_terms', $capabilities);
    }

    /**
     * Test format term.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFormatTerm()
    {
        $term = $this->createMock(\WP_Term::class);
        $term->term_id = 1;
        $term->name = 'Test Category';
        $term->slug = 'test-category';
        $term->taxonomy = 'category';
        $term->description = 'A test category';
        $term->parent = 0;
        $term->count = 5;

        $formatted = $this->detector->formatTerm($term);

        $this->assertIsArray($formatted);
        $this->assertEquals(1, $formatted['id']);
        $this->assertEquals('Test Category', $formatted['name']);
        $this->assertEquals('test-category', $formatted['slug']);
        $this->assertEquals('category', $formatted['taxonomy']);
        $this->assertEquals(5, $formatted['count']);
    }
}
