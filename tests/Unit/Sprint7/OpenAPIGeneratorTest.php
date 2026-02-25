<?php
/**
 * OpenAPI Generator Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Documentation\Generator;
use WP_API_Codeia\Schema\Detector;
use WP_API_Codeia\Utils\Cache\CacheManager;
use WP_API_Codeia\Utils\Logger\Logger;

/**
 * Test OpenAPI Generator class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Documentation\Generator
 */
class OpenAPIGeneratorTest extends TestCase
{
    /**
     * Generator instance.
     *
     * @var Generator
     */
    protected $generator;

    /**
     * Set up test environment.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function setUp(): void
    {
        $cache = new CacheManager(new \WP_API_Codeia\Core\Config());
        $logger = $this->createMock(Logger::class);
        $detector = new Detector($cache, $logger);

        $this->generator = new Generator($detector, $cache, $logger);
    }

    /**
     * Test can generate specification.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanGenerateSpecification()
    {
        $spec = $this->generator->generate();

        $this->assertIsArray($spec);
        $this->assertArrayHasKey('openapi', $spec);
        $this->assertEquals('3.0.0', $spec['openapi']);
    }

    /**
     * Test specification has info section.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testSpecificationHasInfoSection()
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('title', $spec['info']);
        $this->assertArrayHasKey('version', $spec['info']);
        $this->assertEquals('WP API Codeia', $spec['info']['title']);
    }

    /**
     * Test specification has paths.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testSpecificationHasPaths()
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('paths', $spec);
        $this->assertIsArray($spec['paths']);
    }

    /**
     * Test specification has auth paths.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testSpecificationHasAuthPaths()
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('/v1/auth/login', $spec['paths']);
        $this->assertArrayHasKey('/v1/auth/refresh', $spec['paths']);
        $this->assertArrayHasKey('/v1/auth/logout', $spec['paths']);
    }

    /**
     * Test specification has components.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testSpecificationHasComponents()
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('components', $spec);
        $this->assertArrayHasKey('securitySchemes', $spec['components']);
        $this->assertArrayHasKey('schemas', $spec['components']);
        $this->assertArrayHasKey('responses', $spec['components']);
    }

    /**
     * Test has JWT security scheme.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testHasJWTSecurityScheme()
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('bearerAuth', $spec['components']['securitySchemes']);
        $this->assertEquals('http', $spec['components']['securitySchemes']['bearerAuth']['type']);
        $this->assertEquals('bearer', $spec['components']['securitySchemes']['bearerAuth']['scheme']);
    }

    /**
     * Test has response templates.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testHasResponseTemplates()
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('Success', $spec['components']['responses']);
        $this->assertArrayHasKey('Unauthorized', $spec['components']['responses']);
        $this->assertArrayHasKey('Forbidden', $spec['components']['responses']);
        $this->assertArrayHasKey('NotFound', $spec['components']['responses']);
    }

    /**
     * Test export as JSON.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testExportAsJSON()
    {
        $json = $this->generator->exportJson();

        $this->assertIsString($json);
        $this->assertStringContainsString('openapi', $json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
    }

    /**
     * Test export pretty JSON.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testExportPrettyJSON()
    {
        $json = $this->generator->exportJson(true);

        $this->assertStringContainsString("\n", $json);
        $this->assertStringContainsString("  ", $json);
    }

    /**
     * Test can get specification.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanGetSpecification()
    {
        $spec = $this->generator->getSpecification();

        $this->assertIsArray($spec);
        $this->assertEquals('3.0.0', $spec['openapi']);
    }

    /**
     * Test has tags.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testHasTags()
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('tags', $spec);
        $this->assertIsArray($spec['tags']);
    }
}
