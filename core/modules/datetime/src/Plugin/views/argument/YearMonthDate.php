<?php

namespace Drupal\datetime\Plugin\views\argument;

/**
 * Argument handler for a year plus month (CCYYMM).
 *
 * @ViewsArgument("datetime_year_month")
 */
class YearMonthDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Ym';

}
