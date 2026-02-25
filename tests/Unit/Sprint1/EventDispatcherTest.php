<?php
/**
 * Event Dispatcher Test
 *
 * @package WP_API_Codeia\Tests
 */

namespace WP_API_Codeia\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_API_Codeia\Core\EventDispatcher;

/**
 * Test Event Dispatcher class.
 *
 * @since 1.0.0
 *
 * @covers \WP_API_Codeia\Core\EventDispatcher
 */
class EventDispatcherTest extends TestCase
{
    /**
     * Event Dispatcher instance.
     *
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * Set up test environment.
     *
     * @since 1.0.0
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    /**
     * Test can listen to an event.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testCanListenToEvent()
    {
        $executed = false;

        $this->dispatcher->listen('test.event', function () use (&$executed) {
            $executed = true;
        });

        $this->dispatcher->dispatch('test.event');

        $this->assertTrue($executed);
    }

    /**
     * Test listeners receive event name and payload.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testListenersReceiveEventNameAndPayload()
    {
        $receivedEvent = null;
        $receivedPayload = null;

        $this->dispatcher->listen('test.event', function ($event, $payload) use (&$receivedEvent, &$receivedPayload) {
            $receivedEvent = $event;
            $receivedPayload = $payload;
        });

        $this->dispatcher->dispatch('test.event', 'payload_data');

        $this->assertEquals('test.event', $receivedEvent);
        $this->assertEquals('payload_data', $receivedPayload);
    }

    /**
     * Test multiple listeners on same event.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testMultipleListeners()
    {
        $results = array();

        $this->dispatcher->listen('test.event', function () use (&$results) {
            $results[] = 'first';
        });

        $this->dispatcher->listen('test.event', function () use (&$results) {
            $results[] = 'second';
        });

        $this->dispatcher->dispatch('test.event');

        $this->assertCount(2, $results);
        $this->assertContains('first', $results);
        $this->assertContains('second', $results);
    }

    /**
     * Test priority ordering.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testPriorityOrdering()
    {
        $results = array();

        $this->dispatcher->listen('test.event', function () use (&$results) {
            $results[] = 'low';
        }, 10);

        $this->dispatcher->listen('test.event', function () use (&$results) {
            $results[] = 'high';
        }, 5);

        $this->dispatcher->dispatch('test.event');

        // Higher priority (lower number) executes first
        $this->assertEquals('high', $results[0]);
        $this->assertEquals('low', $results[1]);
    }

    /**
     * Test wildcard listeners.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testWildcardListeners()
    {
        $wildcardExecuted = false;
        $specificExecuted = false;

        $this->dispatcher->listen('test.*', function () use (&$wildcardExecuted) {
            $wildcardExecuted = true;
        });

        $this->dispatcher->listen('test.specific', function () use (&$specificExecuted) {
            $specificExecuted = true;
        });

        $this->dispatcher->dispatch('test.specific');

        $this->assertTrue($wildcardExecuted);
        $this->assertTrue($specificExecuted);
    }

    /**
     * Test forget removes listener.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testForget()
    {
        $executed = false;

        $listener = function () use (&$executed) {
            $executed = true;
        };

        $this->dispatcher->listen('test.event', $listener);
        $this->dispatcher->forget('test.event', $listener);

        $this->dispatcher->dispatch('test.event');

        $this->assertFalse($executed);
    }

    /**
     * Test until stops at first non-null result.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testUntilStopsAtFirstNonNull()
    {
        $this->dispatcher->listen('test.event', function () {
            return null;
        });

        $this->dispatcher->listen('test.event', function () {
            return 'first_result';
        });

        $this->dispatcher->listen('test.event', function () {
            return 'second_result';
        });

        $result = $this->dispatcher->until('test.event');

        $this->assertEquals('first_result', $result);
    }

    /**
     * Test hasListeners checks for listeners.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testHasListeners()
    {
        $this->assertFalse($this->dispatcher->hasListeners('test.event'));

        $this->dispatcher->listen('test.event', function () {
            return true;
        });

        $this->assertTrue($this->dispatcher->hasListeners('test.event'));
    }

    /**
     * Test getListenersInfo returns correct info.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetListenersInfo()
    {
        $this->dispatcher->listen('test.event', function () {
            return true;
        });

        $this->dispatcher->listen('test.*', function () {
            return true;
        });

        $info = $this->dispatcher->getListenersInfo('test.event');

        $this->assertArrayHasKey('direct', $info);
        $this->assertArrayHasKey('wildcard', $info);
        $this->assertArrayHasKey('total', $info);
        $this->assertEquals(1, $info['direct']);
        $this->assertEquals(1, $info['wildcard']);
        $this->assertEquals(2, $info['total']);
    }

    /**
     * Test getEvents returns registered events.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testGetEvents()
    {
        $this->dispatcher->listen('event1', function () {
            return true;
        });

        $this->dispatcher->listen('event2', function () {
            return true;
        });

        $events = $this->dispatcher->getEvents();

        $this->assertContains('event1', $events);
        $this->assertContains('event2', $events);
    }

    /**
     * Test dispatch returns all results.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function testDispatchReturnsAllResults()
    {
        $this->dispatcher->listen('test.event', function () {
            return 'result1';
        });

        $this->dispatcher->listen('test.event', function () {
            return 'result2';
        });

        $results = $this->dispatcher->dispatch('test.event');

        $this->assertCount(2, $results);
        $this->assertContains('result1', $results);
        $this->assertContains('result2', $results);
    }
}
