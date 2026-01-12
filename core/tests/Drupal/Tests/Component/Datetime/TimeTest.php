<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Datetime;

use Drupal\Component\Datetime\Time;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Time class.
 *
 * Isolate the tests to prevent side effects from altering system time.
 */
#[CoversClass(\Drupal\Component\Datetime\Time::class)]
#[Group('Datetime')]
#[PreserveGlobalState(FALSE)]
#[RunTestsInSeparateProcesses]
class TimeTest extends TestCase {

  /**
   * The mocked request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $requestStack;

  /**
   * The mocked time class.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->requestStack = $this->getMockBuilder('Symfony\Component\HttpFoundation\RequestStack')->getMock();
    $this->time = new Time($this->requestStack);
  }

  /**
   * Tests the getRequestTime method.
   */
  public function testGetRequestTime(): void {
    $expected = 12345678;

    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_TIME', $expected);

    // Mocks a the request stack getting the current request.
    $this->requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->assertEquals($expected, $this->time->getRequestTime());
  }

  /**
   * Tests the getRequestMicroTime method.
   */
  public function testGetRequestMicroTime(): void {
    $expected = 1234567.89;

    $request = Request::createFromGlobals();
    $request->server->set('REQUEST_TIME_FLOAT', $expected);

    // Mocks a the request stack getting the current request.
    $this->requestStack->expects($this->any())
      ->method('getCurrentRequest')
      ->willReturn($request);

    $this->assertEquals($expected, $this->time->getRequestMicroTime());
  }

  /**
   * Tests get request time no request.
   */
  public function testGetRequestTimeNoRequest(): void {
    // With no request, and no global variable, we expect to get the int part
    // of the microtime.
    $expected = 1234567;
    unset($_SERVER['REQUEST_TIME']);
    $this->assertEquals($expected, $this->time->getRequestTime());
    $_SERVER['REQUEST_TIME'] = 23456789;
    $this->assertEquals(23456789, $this->time->getRequestTime());
  }

  /**
   * Tests get request micro time no request.
   */
  public function testGetRequestMicroTimeNoRequest(): void {
    $expected = 1234567.89;
    unset($_SERVER['REQUEST_TIME_FLOAT']);
    $this->assertEquals($expected, $this->time->getRequestMicroTime());
    $_SERVER['REQUEST_TIME_FLOAT'] = 2345678.90;
    $this->assertEquals(2345678.90, $this->time->getRequestMicroTime());
  }

  /**
   * Tests the getCurrentTime method.
   */
  public function testGetCurrentTime(): void {
    $expected = 12345678;
    $this->assertEquals($expected, $this->time->getCurrentTime());
  }

  /**
   * Tests the getCurrentMicroTime method.
   */
  public function testGetCurrentMicroTime(): void {
    $expected = 1234567.89;
    $this->assertEquals($expected, $this->time->getCurrentMicroTime());
  }

}

namespace Drupal\Component\Datetime;

/**
 * Shadow time() system call.
 *
 * @return int
 *   The fixed integer timestamp used for testing purposes.
 */
function time(): int {
  return 12345678;
}

/**
 * Shadow microtime system call.
 *
 * @return float
 *   The fixed float timestamp used for testing purposes.
 */
function microtime(bool $as_float = FALSE): float {
  return 1234567.89;
}
