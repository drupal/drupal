<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Flood;

use Drupal\Core\Flood\MemoryBackend;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the memory flood implementation.
 */
#[CoversClass(MemoryBackend::class)]
#[Group('flood')]
#[Medium]
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
    $request_mock->method('getClientIp')->willReturn('127.0.0.1');
    $request->push($request_mock);
    $this->flood = new MemoryBackend($request);
  }

  /**
   * Tests an allowed flood event.
   */
  public function testAllowedProceeding(): void {
    $threshold = 2;
    $window_expired = -1;

    $this->flood->register('test_event', $window_expired);
    $this->assertTrue($this->flood->isAllowed('test_event', $threshold));
  }

  /**
   * Tests a flood event with more than the allowed calls.
   */
  public function testNotAllowedProceeding(): void {
    $threshold = 1;
    $window_expired = -1;

    // Register the event twice, so it is not allowed to proceed.
    $this->flood->register('test_event', $window_expired);
    $this->flood->register('test_event', $window_expired, 1);

    $this->assertFalse($this->flood->isAllowed('test_event', $threshold));
  }

  /**
   * Tests a flood event with expiring, so cron will allow to proceed.
   */
  public function testExpiring(): void {
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
  public function testNotExpiring(): void {
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
  public function testMemoryBackendThreshold(): void {
    $this->flood->register('new event');
    $this->assertTrue($this->flood->isAllowed('new event', '2'));
    $this->flood->register('new event');
    $this->assertFalse($this->flood->isAllowed('new event', '2'));
  }

}
