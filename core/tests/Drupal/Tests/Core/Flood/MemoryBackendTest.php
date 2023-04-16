<?php

namespace Drupal\Tests\Core\Flood;

use Drupal\Core\Flood\MemoryBackend;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the memory flood implementation.
 *
 * @group flood
 * @coversDefaultClass \Drupal\Core\Flood\MemoryBackend
 */
class MemoryBackendTest extends UnitTestCase {

  /**
   * The tested memory flood backend.
   *
   * @var \Drupal\Core\Flood\MemoryBackend
   */
  protected $flood;

  protected function setUp(): void {
    parent::setUp();

    $request = new RequestStack();
    $request_mock = $this->getMockBuilder(Request::class)
      ->onlyMethods(['getClientIp'])
      ->getMock();
    $request->push($request_mock);
    $this->flood = new MemoryBackend($request);
  }

  /**
   * Tests an allowed flood event.
   */
  public function testAllowedProceeding() {
    $threshold = 2;
    $window_expired = -1;

    $this->flood->register('test_event', $window_expired);
    $this->assertTrue($this->flood->isAllowed('test_event', $threshold));
  }

  /**
   * Tests a flood event with more than the allowed calls.
   */
  public function testNotAllowedProceeding() {
    $threshold = 1;
    $window_expired = -1;

    // Register the event twice, so it is not allowed to proceed.
    $this->flood->register('test_event', $window_expired);
    $this->flood->register('test_event', $window_expired, 1);

    $this->assertFalse($this->flood->isAllowed('test_event', $threshold));
  }

  /**
   * Tests a flood event with expiring, so cron will allow to proceed.
   *
   * @medium
   */
  public function testExpiring() {
    $threshold = 1;
    $window_expired = -1;

    $this->flood->register('test_event', $window_expired);
    usleep(2);
    $this->flood->register('test_event', $window_expired);

    $this->assertFalse($this->flood->isAllowed('test_event', $threshold));

    // "Run cron", which clears the flood data and verify event is now allowed.
    $this->flood->garbageCollection();
    $this->assertTrue($this->flood->isAllowed('test_event', $threshold));
  }

  /**
   * Tests a flood event with no expiring, so cron will not allow to proceed.
   */
  public function testNotExpiring() {
    $threshold = 2;

    $this->flood->register('test_event', 1);
    usleep(3);
    $this->flood->register('test_event', 1);

    $this->assertFalse($this->flood->isAllowed('test_event', $threshold));

    // "Run cron", which clears the flood data and verify event is not allowed.
    $this->flood->garbageCollection();
    $this->assertFalse($this->flood->isAllowed('test_event', $threshold));
  }

  /**
   * Tests memory backend records events to the nearest microsecond.
   */
  public function testMemoryBackendThreshold() {
    $this->flood->register('new event');
    $this->assertTrue($this->flood->isAllowed('new event', '2'));
    $this->flood->register('new event');
    $this->assertFalse($this->flood->isAllowed('new event', '2'));
  }

}
