<?php

namespace Drupal\Tests\Component\Datetime;

use Drupal\Component\Datetime\Time;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Component\Datetime\Time
 * @group Datetime
 *
 * Isolate the tests to prevent side effects from altering system time.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
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
   *
   * @covers ::getRequestTime
   */
  public function testGetRequestTime() {
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
   *
   * @covers ::getRequestMicroTime
   */
  public function testGetRequestMicroTime() {
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
   * @covers ::getRequestTime
   */
  public function testGetRequestTimeNoRequest() {
    $expected = 12345678;
    unset($_SERVER['REQUEST_TIME']);
    $this->assertEquals($expected, $this->time->getRequestTime());
    $_SERVER['REQUEST_TIME'] = 23456789;
    $this->assertEquals(23456789, $this->time->getRequestTime());
  }

  /**
   * @covers ::getRequestMicroTime
   */
  public function testGetRequestMicroTimeNoRequest() {
    $expected = 1234567.89;
    unset($_SERVER['REQUEST_TIME_FLOAT']);
    $this->assertEquals($expected, $this->time->getRequestMicroTime());
    $_SERVER['REQUEST_TIME_FLOAT'] = 2345678.90;
    $this->assertEquals(2345678.90, $this->time->getRequestMicroTime());
  }

  /**
   * Tests the getCurrentTime method.
   *
   * @covers ::getCurrentTime
   */
  public function testGetCurrentTime() {
    $expected = 12345678;
    $this->assertEquals($expected, $this->time->getCurrentTime());
  }

  /**
   * Tests the getCurrentMicroTime method.
   *
   * @covers ::getCurrentMicroTime
   */
  public function testGetCurrentMicroTime() {
    $expected = 1234567.89;
    $this->assertEquals($expected, $this->time->getCurrentMicroTime());
  }

}

namespace Drupal\Component\Datetime;

/**
 * Shadow time() system call.
 *
 * @returns int
 */
function time() {
  return 12345678;
}

/**
 * Shadow microtime system call.
 *
 * @returns float
 */
function microtime() {
  return 1234567.89;
}
