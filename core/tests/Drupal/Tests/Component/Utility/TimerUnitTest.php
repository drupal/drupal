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

    $this->assertGreaterThanOrEqual(5, $value, 'Timer measured 5 milliseconds of sleeping while running.');
    $this->assertLessThan(10, $value, 'Timer measured 5 milliseconds of sleeping while running.');

    $this->assertGreaterThanOrEqual(10, $value2, 'Timer measured 10 milliseconds of sleeping while running.');
    $this->assertLessThan(15, $value2, 'Timer measured 10 milliseconds of sleeping while running.');

    $this->assertGreaterThanOrEqual(15, $value3, 'Timer measured 15 milliseconds of sleeping while running.');
    $this->assertLessThan(20, $value3, 'Timer measured 15 milliseconds of sleeping while running.');

    $this->assertGreaterThanOrEqual(20, $value4, 'Timer measured 20 milliseconds of sleeping while running.');
    $this->assertLessThan(25, $value4, 'Timer measured 20 milliseconds of sleeping while running.');
  }

}
