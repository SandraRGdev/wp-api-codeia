<?php
/**
 * Auth Manager Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Auth\Manager;
use WP_API_Codeia\Core\Container;
use WP_API_Codeia\Utils\Logger\Logger;
use WP_API_Codeia\Auth\Strategies\AuthStrategyInterface;

/**
 * Test Auth Manager class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Auth\Manager
 */
class AuthManagerTest extends TestCase
{
    /**
     * Auth Manager instance.
     *
     * @var Manager
     */
    protected $authManager;

    /**
     * Container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Mock strategy.
     *
     * @var AuthStrategyInterface
     */
    protected $mockStrategy;

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
        $this->logger = $this->createMock(Logger::class);
        $this->authManager = new Manager($this->container, $this->logger);

        // Create mock strategy
        $this->mockStrategy = $this->createMock(AuthStrategyInterface::class);
    }

    /**
     * Test can register a strategy.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanRegisterStrategy()
    {
        $result = $this->authManager->registerStrategy('test', $this->mockStrategy);

        $this->assertSame($this->authManager, $result);
        $this->assertSame($this->mockStrategy, $this->authManager->getStrategy('test'));
    }

    /**
     * Test can get registered strategy.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanGetRegisteredStrategy()
    {
        $this->authManager->registerStrategy('test', $this->mockStrategy);

        $strategy = $this->authManager->getStrategy('test');

        $this->assertSame($this->mockStrategy, $strategy);
    }

    /**
     * Test get non-existent strategy returns null.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetNonExistentStrategyReturnsNull()
    {
        $strategy = $this->authManager->getStrategy('non_existent');

        $this->assertNull($strategy);
    }

    /**
     * Test can set default strategy.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanSetDefaultStrategy()
    {
        $result = $this->authManager->setDefaultStrategy('custom');

        $this->assertSame($this->authManager, $result);
        $this->assertEquals('custom', $this->authManager->getDefaultStrategy());
    }

    /**
     * Test can get all strategies.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanGetAllStrategies()
    {
        $strategy2 = $this->createMock(AuthStrategyInterface::class);

        $this->authManager->registerStrategy('test1', $this->mockStrategy);
        $this->authManager->registerStrategy('test2', $strategy2);

        $strategies = $this->authManager->getStrategies();

        $this->assertCount(2, $strategies);
        $this->assertArrayHasKey('test1', $strategies);
        $this->assertArrayHasKey('test2', $strategies);
    }

    /**
     * Test authenticate with valid credentials.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testAuthenticateWithValidCredentials()
    {
        // Create mock user
        $user = $this->createMock(\WP_User::class);
        $user->ID = 1;

        // Setup mock strategy
        $this->mockStrategy->method('supports')->willReturn(true);
        $this->mockStrategy->method('authenticate')->willReturn($user);

        $this->authManager->registerStrategy('test', $this->mockStrategy);

        $request = $this->createMock(\WP_REST_Request::class);
        $request->method('get_headers')->willReturn(array());
        $request->method('get_route')->willReturn('/wp-custom-api/v1/test');

        $result = $this->authManager->authenticate($request);

        $this->assertSame($user, $result);
    }

    /**
     * Test authenticate with invalid credentials returns error.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testAuthenticateWithInvalidCredentialsReturnsError()
    {
        $error = new \WP_Error(WP_API_CODEIA_ERROR_AUTH_INVALID, 'Invalid credentials');

        $this->mockStrategy->method('supports')->willReturn(true);
        $this->mockStrategy->method('authenticate')->willReturn($error);

        $this->authManager->registerStrategy('test', $this->mockStrategy);

        $request = $this->createMock(\WP_REST_Request::class);
        $request->method('get_headers')->willReturn(array());

        $result = $this->authManager->authenticate($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
    }

    /**
     * Test generate auth response.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGenerateAuthResponse()
    {
        $user = $this->createMock(\WP_User::class);
        $user->ID = 1;
        $user->user_login = 'testuser';
        $user->user_email = 'test@example.com';
        $user->roles = array('subscriber');

        $response = $this->authManager->generateAuthResponse($user, 'jwt');

        $this->assertIsArray($response);
        $this->assertArrayHasKey('user', $response);
        $this->assertArrayHasKey('strategy', $response);
        $this->assertEquals('jwt', $response['strategy']);
        $this->assertEquals(1, $response['user']['id']);
        $this->assertEquals('testuser', $response['user']['username']);
    }

    /**
     * Test is public endpoint.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testIsPublicEndpoint()
    {
        $publicRoute = '/wp-custom-api/v1/docs';
        $privateRoute = '/wp-custom-api/v1/posts';

        $this->assertTrue($this->authManager->isPublicEndpoint($publicRoute));
        $this->assertFalse($this->authManager->isPublicEndpoint($privateRoute));
    }
}
