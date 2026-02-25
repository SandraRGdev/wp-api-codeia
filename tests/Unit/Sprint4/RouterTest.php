<?php
/**
 * Router Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\API\Router;
use WP_API_Codeia\Core\Container;
use WP_API_Codeia\Schema\Detector;
use WP_API_Codeia\Utils\Logger\Logger;
use WP_API_Codeia\Utils\Cache\CacheManager;

/**
 * Test Router class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\API\Router
 */
class RouterTest extends TestCase
{
    /**
     * Router instance.
     *
     * @var Router
     */
    protected $router;

    /**
     * Container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Set up test environment.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->container = new Container();

        $cache = $this->createMock(CacheManager::class);
        $logger = $this->createMock(Logger::class);
        $detector = new Detector($cache, $logger);

        $this->container->singleton('detector', $detector);
        $this->container->singleton('logger', $logger);
        $this->container->singleton('response_formatter', function () {
            return new \WP_API_Codeia\API\ResponseFormatter();
        });

        $this->router = new Router($this->container, $detector, $logger);
    }

    /**
     * Test router implements service interface.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testRouterImplementsServiceInterface()
    {
        $this->assertInstanceOf(\WP_API_Codeia\Core\Interfaces\ServiceInterface::class, $this->router);
    }

    /**
     * Test can register routes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanRegisterRoutes()
    {
        $this->router->register();

        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test can boot router.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanBootRouter()
    {
        $this->router->boot();

        $this->assertTrue(true); // No exception thrown
    }

    /**
     * Test get post type base.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetPostTypeBase()
    {
        $method = new \ReflectionMethod($this->router, 'getPostTypeBase');
        $method->setAccessible(true);

        $base = $method->invoke($this->router, 'post');

        $this->assertEquals('posts', $base);
    }

    /**
     * Test get taxonomy base.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetTaxonomyBase()
    {
        $method = new \ReflectionMethod($this->router, 'getTaxonomyBase');
        $method->setAccessible(true);

        $base = $method->invoke($this->router, 'category');

        $this->assertEquals('categories', $base);
    }

    /**
     * Test is post type enabled.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testIsPostTypeEnabled()
    {
        $method = new \ReflectionMethod($this->router, 'isPostTypeEnabled');
        $method->setAccessible(true);

        $enabled = $method->invoke($this->router, 'post');

        $this->assertIsBool($enabled);
    }

    /**
     * Test get collection args.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetCollectionArgs()
    {
        $method = new \ReflectionMethod($this->router, 'getCollectionArgs');
        $method->setAccessible(true);

        $args = $method->invoke($this->router);

        $this->assertArrayHasKey('page', $args);
        $this->assertArrayHasKey('per_page', $args);
        $this->assertArrayHasKey('search', $args);
        $this->assertArrayHasKey('orderby', $args);
        $this->assertArrayHasKey('order', $args);
    }

    /**
     * Test get item args.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetItemArgs()
    {
        $method = new \ReflectionMethod($this->router, 'getItemArgs');
        $method->setAccessible(true);

        $args = $method->invoke($this->router, 'post');

        $this->assertArrayHasKey('title', $args);
        $this->assertArrayHasKey('content', $args);
        $this->assertArrayHasKey('status', $args);
    }
}
