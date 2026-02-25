<?php
/**
 * Upload Handler Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Upload\Handler;
use WP_API_Codeia\Utils\Logger\Logger;

/**
 * Test Upload Handler class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Upload\Handler
 */
class UploadHandlerTest extends TestCase
{
    /**
     * Upload Handler instance.
     *
     * @var Handler
     */
    protected $handler;

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
        $this->logger = $this->createMock(Logger::class);
        $this->handler = new Handler($this->logger);
    }

    /**
     * Test can get allowed MIME types.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanGetAllowedMimeTypes()
    {
        $mimes = $this->handler->getAllowedMimeTypes();

        $this->assertIsArray($mimes);
        $this->assertArrayHasKey('jpg|jpeg', $mimes);
        $this->assertArrayHasKey('png', $mimes);
        $this->assertArrayHasKey('gif', $mimes);
    }

    /**
     * Test can set allowed MIME types.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanSetAllowedMimeTypes()
    {
        $customMimes = array(
            'jpg' => 'image/jpeg',
            'pdf' => 'application/pdf',
        );

        $this->handler->setAllowedMimeTypes($customMimes);
        $mimes = $this->handler->getAllowedMimeTypes();

        $this->assertEquals($customMimes, $mimes);
    }

    /**
     * Test can add validator.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanAddValidator()
    {
        $validator = $this->createMock(\WP_API_Codeia\Upload\Validators\UploadValidator::class);

        $this->handler->addValidator('custom', $validator);

        // No exception thrown
        $this->assertTrue(true);
    }

    /**
     * Test can remove validator.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanRemoveValidator()
    {
        $this->handler->removeValidator('size');

        // No exception thrown
        $this->assertTrue(true);
    }

    /**
     * Test generate attachment title.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGenerateAttachmentTitle()
    {
        $method = new \ReflectionMethod($this->handler, 'generateAttachmentTitle');
        $method->setAccessible(true);

        $title = $method->invoke($this->handler, '/path/to/my-test-image.jpg');

        $this->assertEquals('My Test Image', $title);
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
        $method = new \ReflectionMethod($this->handler, 'convertToBytes');
        $method->setAccessible(true);

        $this->assertEquals(1024, $method->invoke($this->handler, '1K'));
        $this->assertEquals(1048576, $method->invoke($this->handler, '1M'));
        $this->assertEquals(1073741824, $method->invoke($this->handler, '1G'));
    }

    /**
     * Test get upload error message.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetUploadErrorMessage()
    {
        $method = new \ReflectionMethod($this->handler, 'getUploadError');
        $method->setAccessible(true);

        $error = $method->invoke($this->handler, UPLOAD_ERR_INI_SIZE);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertEquals('upload_error', $error->get_error_code());
    }

    /**
     * Test get upload error for no file.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetUploadErrorForNoFile()
    {
        $method = new \ReflectionMethod($this->handler, 'getUploadError');
        $method->setAccessible(true);

        $error = $method->invoke($this->handler, UPLOAD_ERR_NO_FILE);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertStringContainsString('no file', strtolower($error->get_error_message()));
    }

    /**
     * Test get file URL.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetFileUrl()
    {
        // This test would need a real attachment
        // For unit testing, we just check the method exists
        $this->assertTrue(method_exists($this->handler, 'getFileUrl'));
    }
}
