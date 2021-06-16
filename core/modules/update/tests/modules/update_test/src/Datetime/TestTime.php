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

}
