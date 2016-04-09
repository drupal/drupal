<?php

namespace Drupal\datetime\Plugin\views\argument;

/**
 * Argument handler for a week.
 *
 * @ViewsArgument("datetime_week")
 */
class WeekDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'W';

}
