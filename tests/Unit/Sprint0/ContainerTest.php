<?php
/**
 * Container Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Core\Container;

/**
 * Test dependency injection container.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Core\Container
 */
class ContainerTest extends TestCase
{
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
    }

    /**
     * Test container can bind a service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanBindService()
    {
        $this->container->bind('test.service', function () {
            return 'test-value';
        });

        $this->assertTrue($this->container->has('test.service'));
    }

    /**
     * Test container can resolve a service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanMakeService()
    {
        $this->container->bind('test.service', function () {
            return 'test-value';
        });

        $result = $this->container->make('test.service');

        $this->assertEquals('test-value', $result);
    }

    /**
     * Test container singleton binding.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testSingletonBinding()
    {
        $counter = 0;

        $this->container->singleton('test.counter', function () use (&$counter) {
            return ++$counter;
        });

        $first = $this->container->make('test.counter');
        $second = $this->container->make('test.counter');

        $this->assertEquals(1, $first);
        $this->assertEquals(1, $second); // Should return same instance
    }

    /**
     * Test container returns same instance for singleton.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testSingletonReturnsSameInstance()
    {
        $this->container->singleton('test.service', function () {
            return new \stdClass();
        });

        $first = $this->container->make('test.service');
        $second = $this->container->make('test.service');

        $this->assertSame($first, $second);
    }

    /**
     * Test container with concrete value.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanBindConcreteValue()
    {
        $this->container->bind('test.value', 'concrete-value');

        $result = $this->container->make('test.value');

        $this->assertEquals('concrete-value', $result);
    }

    /**
     * Test container throws exception for undefined service.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testThrowsExceptionForUndefinedService()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service "undefined.service" not found in container.');

        $this->container->make('undefined.service');
    }

    /**
     * Test container has method.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testHasMethod()
    {
        $this->assertFalse($this->container->has('test.service'));

        $this->container->bind('test.service', 'value');

        $this->assertTrue($this->container->has('test.service'));
    }

    /**
     * Test get method creates singleton if not exists.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetCreatesSingleton()
    {
        $result = $this->container->get('test.get', 'default-value');

        $this->assertEquals('default-value', $result);
        $this->assertTrue($this->container->has('test.get'));
    }

    /**
     * Test get returns existing singleton.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetReturnsExistingSingleton()
    {
        $this->container->singleton('test.get', function () {
            return new \stdClass();
        });

        $first = $this->container->get('test.get');
        $second = $this->container->get('test.get');

        $this->assertSame($first, $second);
    }

    /**
     * Test closure receives container as parameter.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testClosureReceivesContainer()
    {
        $this->container->bind('test.container', function ($container) {
            return $container instanceof Container;
        });

        $result = $this->container->make('test.container');

        $this->assertTrue($result);
    }
}
