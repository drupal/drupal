<?php

namespace Drupal\datetime\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;

/**
 * Argument handler for a month.
 */
#[ViewsArgument(
  id: 'datetime_month',
)]
class MonthDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'm';

}
