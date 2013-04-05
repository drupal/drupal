<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\Utility\TimerUnitTest.
 */

namespace Drupal\Tests\Component\Utility;

use Drupal\Tests\UnitTestCase;
use Drupal\Component\Utility\Timer;

/**
 * Tests the Timer system.
 *
 * @see \Drupal\Component\Utility\Timer
 */
class TimerUnitTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Timer test',
      'description' => 'Test that Timer::read() works both when a timer is running and when a timer is stopped.',
      'group' => 'Bootstrap',
    );
  }

  /**
   * Tests Timer::read() time accumulation accuracy across multiple restarts.
   *
   * @see Drupal\Component\Utility\Timer::read()
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

    $this->assertGreaterThanOrEqual(5, $value, 'Timer measured at least 5 milliseconds of sleeping while running.');

    $this->assertGreaterThanOrEqual($value + 5, $value2, 'Timer measured at least 10 milliseconds of sleeping while running.');

    $this->assertGreaterThanOrEqual($value2 + 5, $value3, 'Timer measured at least 15 milliseconds of sleeping while running.');

    $this->assertGreaterThanOrEqual($value3 + 5, $value4, 'Timer measured at least 20 milliseconds of sleeping while running.');
  }

}
