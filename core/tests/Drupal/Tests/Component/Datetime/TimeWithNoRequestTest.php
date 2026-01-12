<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Datetime;

use Drupal\Component\Datetime\Time;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Tests that getRequest(Micro)Time works when no underlying request exists.
 */
#[CoversClass(Time::class)]
#[Group('Datetime')]
#[Group('#slow')]
#[PreserveGlobalState(FALSE)]
#[RunTestsInSeparateProcesses]
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
   */
  public function testGetRequestTimeImmutable(): void {
    $requestTime = $this->time->getRequestTime();
    sleep(2);
    $this->assertSame($requestTime, $this->time->getRequestTime());
  }

  /**
   * Tests the getRequestMicroTime method.
   */
  public function testGetRequestMicroTimeImmutable(): void {
    $requestTime = $this->time->getRequestMicroTime();
    usleep(20000);
    $this->assertSame($requestTime, $this->time->getRequestMicroTime());
  }

}
