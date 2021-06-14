<?php

namespace Drupal\datetime\Plugin\views\argument;

/**
 * Argument handler for a day.
 *
 * @ViewsArgument("datetime_day")
 */
class DayDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'd';

}
