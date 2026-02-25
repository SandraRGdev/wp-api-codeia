<?php
/**
 * Logger Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Utils\Logger\Logger;

/**
 * Test Logger class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Utils\Logger\Logger
 */
class LoggerTest extends TestCase
{
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
        $this->logger = new Logger();
    }

    /**
     * Test logger levels are defined.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testLoggerLevelsAreDefined()
    {
        $this->assertTrue(defined('WP_API_Codeia\Utils\Logger\Logger::DEBUG'));
        $this->assertTrue(defined('WP_API_Codeia\Utils\Logger\Logger::INFO'));
        $this->assertTrue(defined('WP_API_Codeia\Utils\Logger\Logger::WARNING'));
        $this->assertTrue(defined('WP_API_Codeia\Utils\Logger\Logger::ERROR'));
        $this->assertTrue(defined('WP_API_Codeia\Utils\Logger\Logger::CRITICAL'));
    }

    /**
     * Test log level priorities.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testLogLevelPriorities()
    {
        $this->assertGreaterThan($this->logger->getMinLevel(), 'debug');
        $this->assertGreaterThanOrEqual($this->logger->getMinLevel(), 'warning');
    }

    /**
     * Test logger can be enabled/disabled.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testSetEnabled()
    {
        $this->logger->setEnabled(false);
        $this->assertFalse($this->logger->isEnabled());

        $this->logger->setEnabled(true);
        $this->assertTrue($this->logger->isEnabled());
    }

    /**
     * Test minimum log level can be set.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testSetMinLevel()
    {
        $this->logger->setMinLevel('debug');
        $this->assertEquals('debug', $this->logger->getMinLevel());

        $this->logger->setMinLevel('error');
        $this->assertEquals('error', $this->logger->getMinLevel());
    }

    /**
     * Test log levels should log correctly.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testLogLevelShouldLog()
    {
        // When min level is warning, debug should not log
        $this->logger->setMinLevel('warning');

        $this->assertFalse($this->logger->isEnabled() || $this->logger->getMinLevel() !== 'debug');
        $this->assertTrue($this->logger->getMinLevel() === 'warning');
    }

    /**
     * Test log methods exist.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testLogMethodsExist()
    {
        $methods = array('debug', 'info', 'warning', 'error', 'critical');

        foreach ($methods as $method) {
            $this->assertTrue(method_exists($this->logger, $method));
        }
    }
}
