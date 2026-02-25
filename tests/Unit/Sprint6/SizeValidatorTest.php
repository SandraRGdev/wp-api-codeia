<?php
/**
 * Size Validator Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Upload\Validators\SizeValidator;

/**
 * Test Size Validator class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Upload\Validators\SizeValidator
 */
class SizeValidatorTest extends TestCase
{
    /**
     * Size Validator instance.
     *
     * @var SizeValidator
     */
    protected $validator;

    /**
     * Set up test environment.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->validator = new SizeValidator(1048576); // 1MB
    }

    /**
     * Test validates file within limit.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testValidatesFileWithinLimit()
    {
        $file = array(
            'name' => 'test.jpg',
            'size' => 512000, // 500KB
            'tmp_name' => '/tmp/test',
            'type' => 'image/jpeg',
        );

        $result = $this->validator->validate($file);

        $this->assertTrue($result);
    }

    /**
     * Test validates file exactly at limit.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testValidatesFileAtLimit()
    {
        $file = array(
            'name' => 'test.jpg',
            'size' => 1048576, // Exactly 1MB
            'tmp_name' => '/tmp/test',
            'type' => 'image/jpeg',
        );

        $result = $this->validator->validate($file);

        $this->assertTrue($result);
    }

    /**
     * Test rejects file over limit.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testRejectsFileOverLimit()
    {
        $file = array(
            'name' => 'test.jpg',
            'size' => 2097152, // 2MB
            'tmp_name' => '/tmp/test',
            'type' => 'image/jpeg',
        );

        $result = $this->validator->validate($file);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('file_too_large', $result->get_error_code());
    }

    /**
     * Test uses custom max size from options.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testUsesCustomMaxSizeFromOptions()
    {
        $file = array(
            'name' => 'test.jpg',
            'size' => 2097152, // 2MB
            'tmp_name' => '/tmp/test',
            'type' => 'image/jpeg',
        );

        $options = array(
            'max_file_size' => 5242880, // 5MB
        );

        $result = $this->validator->validate($file, $options);

        $this->assertTrue($result);
    }

    /**
     * Test can get max size.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanGetMaxSize()
    {
        $maxSize = $this->validator->getMaxSize();

        $this->assertEquals(1048576, $maxSize);
    }

    /**
     * Test can set max size.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanSetMaxSize()
    {
        $this->validator->setMaxSize(5242880);

        $this->assertEquals(5242880, $this->validator->getMaxSize());
    }
}
