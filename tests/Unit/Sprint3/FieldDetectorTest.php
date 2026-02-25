<?php
/**
 * Field Detector Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Schema\FieldDetector;
use WP_API_Codeia\Utils\Cache\CacheManager;
use WP_API_Codeia\Utils\Logger\Logger;

/**
 * Test Field Detector class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Schema\FieldDetector
 */
class FieldDetectorTest extends TestCase
{
    /**
     * Field Detector instance.
     *
     * @var FieldDetector
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
        $this->detector = new FieldDetector($this->cache, $this->logger);
    }

    /**
     * Test can detect fields for post type.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanDetectFieldsForPostType()
    {
        $fields = $this->detector->detectForPostType('post');

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('native', $fields);
        $this->assertArrayHasKey('meta', $fields);
        $this->assertArrayHasKey('taxonomy', $fields);
    }

    /**
     * Test native fields are detected.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testNativeFieldsAreDetected()
    {
        $fields = $this->detector->detectForPostType('post');

        $this->assertArrayHasKey('id', $fields['native']);
        $this->assertArrayHasKey('title', $fields['native']);
        $this->assertArrayHasKey('content', $fields['native']);
        $this->assertArrayHasKey('status', $fields['native']);
    }

    /**
     * Test native field structure.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testNativeFieldStructure()
    {
        $fields = $this->detector->detectForPostType('post');

        $titleField = $fields['native']['title'];

        $this->assertArrayHasKey('name', $titleField);
        $this->assertArrayHasKey('type', $titleField);
        $this->assertArrayHasKey('description', $titleField);
        $this->assertEquals('title', $titleField['name']);
        $this->assertEquals('string', $titleField['type']);
    }

    /**
     * Test readonly fields are marked.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testReadonlyFieldsAreMarked()
    {
        $fields = $this->detector->detectForPostType('post');

        $this->assertTrue($fields['native']['id']['readonly']);
        $this->assertTrue($fields['native']['modified']['readonly']);
        $this->assertFalse($fields['native']['title']['readonly']);
    }

    /**
     * Test get all fields returns combined array.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetAllFieldsReturnsCombinedArray()
    {
        $allFields = $this->detector->getAllFields('post');

        $this->assertIsArray($allFields);
        $this->assertArrayHasKey('id', $allFields);
        $this->assertArrayHasKey('title', $allFields);
        $this->assertArrayHasKey('content', $allFields);
    }

    /**
     * Test field schema is generated.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFieldSchemaIsGenerated()
    {
        $schema = $this->detector->getFieldSchema('post');

        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
    }

    /**
     * Test field schema properties.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFieldSchemaProperties()
    {
        $schema = $this->detector->getFieldSchema('post');

        $this->assertArrayHasKey('id', $schema['properties']);
        $this->assertArrayHasKey('title', $schema['properties']);
        $this->assertArrayHasKey('content', $schema['properties']);
    }

    /**
     * Test field to schema conversion.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFieldToSchemaConversion()
    {
        $field = array(
            'name' => 'test_field',
            'type' => 'text',
            'description' => 'Test field description',
            'readonly' => false,
        );

        // Use reflection to test protected method
        $reflection = new \ReflectionClass($this->detector);
        $method = $reflection->getMethod('fieldToSchema');
        $method->setAccessible(true);

        $schema = $method->invoke($this->detector, $field);

        $this->assertEquals('string', $schema['type']);
        $this->assertEquals('Test field description', $schema['description']);
    }

    /**
     * Test taxonomy fields are detected.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testTaxonomyFieldsAreDetected()
    {
        $fields = $this->detector->detectForPostType('post');

        $this->assertIsArray($fields['taxonomy']);
        // Post has category and tag taxonomies
        $this->assertArrayHasKey('category', $fields['taxonomy']);
        $this->assertArrayHasKey('post_tag', $fields['taxonomy']);
    }
}
