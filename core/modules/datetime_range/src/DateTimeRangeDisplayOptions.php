<?php

namespace Drupal\datetime_range;

/**
 * Declares constants used in the Datetime Range module.
 */
enum DateTimeRangeDisplayOptions: string {

  // Values for the 'from_to' formatter setting.
  case Both = 'both';
  case StartDate = 'start_date';
  case EndDate = 'end_date';

}
