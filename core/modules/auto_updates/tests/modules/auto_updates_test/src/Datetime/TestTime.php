<?php

namespace Drupal\auto_updates_test\Datetime;

use Drupal\Component\Datetime\Time;

/**
 * Test service for altering the request time.
 */
class TestTime extends Time {

  /**
   * {@inheritdoc}
   */
  public function getRequestTime(): int {
    if ($faked_date = \Drupal::state()->get('auto_updates_test.fake_date_time')) {
      return \DateTime::createFromFormat('U', $faked_date)->getTimestamp();
    }
    return parent::getRequestTime();
  }

  /**
   * Sets a fake time from an offset that will be used in the test.
   *
   * @param string $offset
   *   A date/time offset string as used by \DateTime::modify.
   */
  public static function setFakeTimeByOffset(string $offset): void {
    $fake_time = (new \DateTime())->modify($offset)->format('U');
    \Drupal::state()->set('auto_updates_test.fake_date_time', $fake_time);
  }

}
