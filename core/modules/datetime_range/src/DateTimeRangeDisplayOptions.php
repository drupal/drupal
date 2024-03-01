<?php

namespace Drupal\datetime_range;

/**
 * Declares constants used in the datetime range module.
 */
enum DateTimeRangeDisplayOptions: string {

  /**
   * Values for the 'from_to' formatter setting.
   */
  case BOTH = 'both';
  case START_DATE = 'start_date';
  case END_DATE = 'end_date';

}
