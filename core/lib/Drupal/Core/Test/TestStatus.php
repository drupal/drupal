<?php

namespace Drupal\Core\Test;

/**
 * Consolidates test result status information.
 *
 * For our test runners, a $status of 0 = passed test, 1 = failed test,
 * 2 = errored test, >2 indicates segfault timeout, or other type of system
 * failure.
 */
class TestStatus {

  /**
   * Signify that the test result was a passed test.
   */
  const PASS = 0;

  /**
   * Signify that the test result was a failed test.
   */
  const FAIL = 1;

  /**
   * Signify that the test result was a code error.
   *
   * This means that the test runner was able to exit and report an error.
   */
  const ERROR = 2;

  /**
   * Signify a system error where the test runner was unable to complete.
   *
   * Note that SYSTEM actually represents the lowest value of system errors, and
   * the returned value could be as high as 127. Since that's the case, this
   * constant should be used for range comparisons, and not just for equality.
   *
   * @see http://php.net/manual/pcntl.constants.php
   */
  const SYSTEM = 3;

  /**
   * Turns a status code into a human-readable string.
   *
   * @param int $status
   *   A test runner return code.
   *
   * @return string
   *   The human-readable version of the status code.
   */
  public static function label($status) {
    $statusMap = [
      static::PASS => 'pass',
      static::FAIL => 'fail',
      static::ERROR => 'error',
      static::SYSTEM => 'exception',
    ];
    // For status 3 and higher, we want 'exception.'
    $label = $statusMap[$status > static::SYSTEM ? static::SYSTEM : $status];
    return $label;
  }

}
