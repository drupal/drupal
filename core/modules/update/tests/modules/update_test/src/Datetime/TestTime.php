<?php

namespace Drupal\update_test\Datetime;

use Drupal\Component\Datetime\Time;

/**
 * Test service for altering the request time.
 */
class TestTime extends Time {

  /**
   * {@inheritdoc}
   */
  public function getRequestTime() {
    if ($mock_date = \Drupal::state()->get('update_test.mock_date', NULL)) {
      return \DateTime::createFromFormat('Y-m-d', $mock_date)->getTimestamp();
    }
    return parent::getRequestTime();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentTime() {
    // Ensure that request for the current time returns any previously set mock
    // date. If a mock date is not set then return the real current time.
    if ($mock_date = \Drupal::state()->get('update_test.mock_date', NULL)) {
      return \DateTime::createFromFormat('Y-m-d', $mock_date)->getTimestamp();
    }
    return parent::getCurrentTime();
  }

}
