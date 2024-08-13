<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Datetime;

use Drupal\Component\Datetime\Time;
use PHPUnit\Framework\TestCase;

/**
 * Tests that getRequest(Micro)Time works when no underlying request exists.
 *
 * @coversDefaultClass \Drupal\Component\Datetime\Time
 * @group Datetime
 * @group #slow
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class TimeWithNoRequestTest extends TestCase {

  /**
   * The time class for testing.
   */
  protected Time $time;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // We need to explicitly unset the $_SERVER variables, so that Time is
    // forced to look for current time.
    unset($_SERVER['REQUEST_TIME']);
    unset($_SERVER['REQUEST_TIME_FLOAT']);

    $this->time = new Time();
  }

  /**
   * Tests the getRequestTime method.
   *
   * @covers ::getRequestTime
   */
  public function testGetRequestTimeImmutable(): void {
    $requestTime = $this->time->getRequestTime();
    sleep(2);
    $this->assertSame($requestTime, $this->time->getRequestTime());
  }

  /**
   * Tests the getRequestMicroTime method.
   *
   * @covers ::getRequestMicroTime
   */
  public function testGetRequestMicroTimeImmutable(): void {
    $requestTime = $this->time->getRequestMicroTime();
    usleep(20000);
    $this->assertSame($requestTime, $this->time->getRequestMicroTime());
  }

}
