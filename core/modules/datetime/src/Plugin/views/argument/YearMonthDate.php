<?php

namespace Drupal\datetime\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;

/**
 * Argument handler for a year plus month (CCYYMM).
  */
#[ViewsArgument(
  id: 'datetime_year_month',
)]
class YearMonthDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Ym';

}
