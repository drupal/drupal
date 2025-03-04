<?php

namespace Drupal\datetime_range;

/**
 * Declares constants used in the datetime range module.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. Use
 *    enum DateTimeRangeDisplayOptions instead.
 * @see https://www.drupal.org/node/3495241
 */
interface DateTimeRangeConstantsInterface {

  /**
   * Values for the 'from_to' formatter setting.
   */
  const BOTH = 'both';
  const START_DATE = 'start_date';
  const END_DATE = 'end_date';

}
