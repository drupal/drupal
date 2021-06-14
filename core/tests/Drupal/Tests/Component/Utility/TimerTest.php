<?php

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\Timer;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Timer system.
 *
 * @group Utility
 *
 * @coversDefaultClass \Drupal\Component\Utility\Timer
 */
class TimerTest extends TestCase {

  /**
   * Tests Timer::read() time accumulation accuracy across multiple restarts.
   *
   * @covers ::start
   * @covers ::stop
   * @covers ::read
   */
  public function testTimer() {
    Timer::start('test');
    usleep(5000);
    $value = Timer::read('test');
    usleep(5000);
    $value2 = Timer::read('test');
    usleep(5000);
    $value3 = Timer::read('test');
    usleep(5000);
    $value4 = Timer::read('test');

    // Although we sleep for 5 milliseconds, we should test that at least 4 ms
    // have past because usleep() is not reliable on Windows. See
    // http://php.net/manual/function.usleep.php for more information. The
    // purpose of the test to validate that the Timer class can measure elapsed
    // time not the granularity of usleep() on a particular OS.
    $this->assertGreaterThanOrEqual(4, $value, 'Timer failed to measure at least 4 milliseconds of sleeping while running.');

    $this->assertGreaterThanOrEqual($value + 4, $value2, 'Timer failed to measure at least 8 milliseconds of sleeping while running.');

    $this->assertGreaterThanOrEqual($value2 + 4, $value3, 'Timer failed to measure at least 12 milliseconds of sleeping while running.');

    $this->assertGreaterThanOrEqual($value3 + 4, $value4, 'Timer failed to measure at least 16 milliseconds of sleeping while running.');

    // Stop the timer.
    $value5 = Timer::stop('test');
    $this->assertGreaterThanOrEqual($value4, $value5['time'], 'Timer measured after stopping was not greater than last measurement.');

    // Read again.
    $value6 = Timer::read('test');
    $this->assertEquals($value5['time'], $value6, 'Timer measured after stopping was not equal to the stopped time.');

    // Restart.
    Timer::start('test');
    usleep(5000);
    $value7 = Timer::read('test');
    $this->assertGreaterThanOrEqual($value6 + 4, $value7, 'Timer failed to measure at least 16 milliseconds of sleeping while running.');

    // Stop again.
    $value8 = Timer::stop('test');
    $value9 = Timer::read('test');
    $this->assertEquals($value8['time'], $value9, 'Timer measured after stopping not equal to stop time.');
  }

}
