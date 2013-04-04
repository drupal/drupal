<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\Timer.
 */

namespace Drupal\Component\Utility;

/**
 * Provides helpers to use timers throughout a request.
 */
class Timer {

  static protected $timers = array();

  /**
   * Starts the timer with the specified name.
   *
   * If you start and stop the same timer multiple times, the measured intervals
   * will be accumulated.
   *
   * @param $name
   *   The name of the timer.
   */
  static public function start($name) {
    static::$timers[$name]['start'] = microtime(TRUE);
    static::$timers[$name]['count'] = isset(static::$timers[$name]['count']) ? ++static::$timers[$name]['count'] : 1;
  }

  /**
   * Reads the current timer value without stopping the timer.
   *
   * @param string $name
   *   The name of the timer.
   *
   * @return int
   *   The current timer value in ms.
   */
  static public function read($name) {
    if (isset(static::$timers[$name]['start'])) {
      $stop = microtime(TRUE);
      $diff = round(($stop - static::$timers[$name]['start']) * 1000, 2);

      if (isset(static::$timers[$name]['time'])) {
        $diff += static::$timers[$name]['time'];
      }
      return $diff;
    }
    return static::$timers[$name]['time'];
  }

  /**
   * Stops the timer with the specified name.
   *
   * @param string $name
   *   The name of the timer.
   *
   * @return array
   *   A timer array. The array contains the number of times the timer has been
   *   started and stopped (count) and the accumulated timer value in ms (time).
   */
  static public function stop($name) {
    if (isset(static::$timers[$name]['start'])) {
      $stop = microtime(TRUE);
      $diff = round(($stop - static::$timers[$name]['start']) * 1000, 2);
      if (isset(static::$timers[$name]['time'])) {
        static::$timers[$name]['time'] += $diff;
      }
      else {
        static::$timers[$name]['time'] = $diff;
      }
      unset(static::$timers[$name]['start']);
    }

    return static::$timers[$name];
  }

}
