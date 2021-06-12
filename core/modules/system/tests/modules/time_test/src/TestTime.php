<?php

namespace Drupal\time_test;

use Drupal\Component\Datetime\Time;

/**
 * Test service for altering the request time.
 */
class TestTime extends Time {

  private const STATE_KEY = 'TEST_TIME.REQUEST_TIME';

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    if ($test_request_time = \Drupal::state()->get(self::STATE_KEY, NULL)) {
      return $test_request_time;
    }
    return parent::getRequestTime();
  }

  /**
   * Set the test request time.
   *
   * @param string $date_time
   *   The date time string as passed to \DateTime::createFromFormat().
   * @param string $format
   *   (optional) The date time format as passed to
   *   \DateTime::createFromFormat(). Defaults to 'U' for unix timestamp format.
   */
  public static function setRequestTime(string $date_time, string $format = 'U'): void {
    \Drupal::state()->set(
      self::STATE_KEY,
      \DateTime::createFromFormat($format, $date_time)->getTimestamp()
    );
  }

}
