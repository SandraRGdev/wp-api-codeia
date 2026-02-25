<?php
/**
 * Admin Page Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Admin\Page;

/**
 * Test Admin Page class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Admin\Page
 */
class AdminPageTest extends TestCase
{
    /**
     * Admin Page instance.
     *
     * @var Page
     */
    protected $page;

    /**
     * Set up test environment.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->page = new Page();
    }

    /**
     * Test can get page slug.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanGetPageSlug()
    {
        $slug = $this->page->slug;

        $this->assertEquals('wp-api-codeia', $slug);
    }

    /**
     * Test page implements service interface.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testPageImplementsServiceInterface()
    {
        $this->assertInstanceOf(\WP_API_Codeia\Core\Interfaces\ServiceInterface::class, $this->page);
    }

    /**
     * Test convert to bytes.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testConvertToBytes()
    {
        $method = new \ReflectionMethod($this->page, 'convertToBytes');
        $method->setAccessible(true);

        $this->assertEquals(1024, $method->invoke($this->page, '1K'));
        $this->assertEquals(1048576, $method->invoke($this->page, '1M'));
        $this->assertEquals(1073741824, $method->invoke($this->page, '1G'));
    }
}
