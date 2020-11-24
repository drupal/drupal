<?php

namespace Drupal\auto_updates_test\Datetime;

use Drupal\Component\Datetime\Time;

/**
 * Test service for altering the request time.
 */
class TestTime extends Time {

  /**
   * The time format to for setting the test time.
   */
  const TIME_FORMAT = 'U';

  /**
   * The state key for setting and getting the test time.
   */
  const STATE_KEY = 'auto_updates_test.mock_date_time';

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    if ($faked_date = \Drupal::state()->get(TestTime::STATE_KEY)) {
      return \DateTime::createFromFormat(self::TIME_FORMAT, $faked_date)->getTimestamp();
    }
    return parent::getRequestTime();
  }

}
