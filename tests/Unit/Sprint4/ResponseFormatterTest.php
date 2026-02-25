<?php
/**
 * Response Formatter Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\API\ResponseFormatter;

/**
 * Test Response Formatter class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\API\ResponseFormatter
 */
class ResponseFormatterTest extends TestCase
{
    /**
     * Response Formatter instance.
     *
     * @var ResponseFormatter
     */
    protected $formatter;

    /**
     * Set up test environment.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->formatter = new ResponseFormatter();
    }

    /**
     * Test format success response.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFormatSuccessResponse()
    {
        $data = array('key' => 'value');
        $response = $this->formatter->success($data);

        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals($data, $response['data']);
        $this->assertArrayHasKey('meta', $response);
    }

    /**
     * Test format success response with message.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFormatSuccessResponseWithMessage()
    {
        $response = $this->formatter->success(array(), 'Test message');

        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('Test message', $response['message']);
    }

    /**
     * Test format error response.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFormatErrorResponse()
    {
        $error = $this->formatter->error('test_error', 'Test error message', 400);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertEquals('test_error', $error->get_error_code());
        $this->assertEquals('Test error message', $error->get_error_message());
    }

    /**
     * Test format collection response.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFormatCollectionResponse()
    {
        $items = array(
            array('id' => 1, 'name' => 'Item 1'),
            array('id' => 2, 'name' => 'Item 2'),
        );

        $response = $this->formatter->collection($items, 25, 1, 10);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('items', $response['data']);
        $this->assertArrayHasKey('pagination', $response['data']);
        $this->assertCount(2, $response['data']['items']);
        $this->assertEquals(25, $response['data']['pagination']['total_items']);
        $this->assertEquals(3, $response['data']['pagination']['total_pages']);
    }

    /**
     * Test format item response.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFormatItemResponse()
    {
        $item = array('id' => 1, 'name' => 'Test Item');
        $response = $this->formatter->item($item);

        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals($item, $response['data']);
    }

    /**
     * Test format created response.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFormatCreatedResponse()
    {
        $item = array('id' => 1);
        $response = $this->formatter->created($item);

        $this->assertArrayHasKey('message', $response);
        $this->assertStringContainsString('created', strtolower($response['message']));
    }

    /**
     * Test format updated response.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFormatUpdatedResponse()
    {
        $item = array('id' => 1);
        $response = $this->formatter->updated($item);

        $this->assertArrayHasKey('message', $response);
        $this->assertStringContainsString('updated', strtolower($response['message']));
    }

    /**
     * Test format deleted response.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testFormatDeletedResponse()
    {
        $item = array('id' => 1);
        $response = $this->formatter->deleted($item);

        $this->assertArrayHasKey('message', $response);
        $this->assertStringContainsString('deleted', strtolower($response['message']));
    }

    /**
     * Test meta is included in response.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testMetaIsIncludedInResponse()
    {
        $response = $this->formatter->success(array());

        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('timestamp', $response['meta']);
        $this->assertArrayHasKey('request_id', $response['meta']);
        $this->assertArrayHasKey('version', $response['meta']);
    }

    /**
     * Test validation error.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testValidationError()
    {
        $errors = array(
            'title' => 'Title is required',
            'email' => 'Invalid email format',
        );

        $error = $this->formatter->validationError($errors);

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertEquals(WP_API_CODEIA_ERROR_VALIDATION_FAILED, $error->get_error_code());
    }

    /**
     * Test not found error.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testNotFoundError()
    {
        $error = $this->formatter->notFoundError('Post');

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertEquals(WP_API_CODEIA_ERROR_NOT_FOUND, $error->get_error_code());
        $this->assertStringContainsString('not found', strtolower($error->get_error_message()));
    }

    /**
     * Test forbidden error.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testForbiddenError()
    {
        $error = $this->formatter->forbiddenError();

        $this->assertInstanceOf(\WP_Error::class, $error);
        $this->assertEquals(WP_API_CODEIA_ERROR_FORBIDDEN, $error->get_error_code());
    }
}
