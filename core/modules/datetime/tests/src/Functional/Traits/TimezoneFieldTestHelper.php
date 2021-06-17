<?php

namespace Drupal\Tests\datetime\Functional\Traits;

/**
 * Provides helpers for testing time function functionality.
 */
trait TimezoneFieldTestHelper {

  /**
   * An array of time zone extremes to test.
   *
   * @var string[]
   */
  public $timezones = [
    // UTC-12, no DST.
    'Pacific/Kwajalein',
    // UTC-11, no DST
    'Pacific/Midway',
    // UTC-7, no DST.
    'America/Phoenix',
    // UTC.
    'UTC',
    // UTC+5:30, no DST.
    'Asia/Kolkata',
    // UTC+12, no DST
    'Pacific/Funafuti',
    // UTC+13, no DST.
    'Pacific/Tongatapu',
  ];

}
