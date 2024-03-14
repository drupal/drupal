<?php

namespace Drupal\datetime\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;

/**
 * Argument handler for a week.
 */
#[ViewsArgument(
  id: 'datetime_week'
)]
class WeekDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'W';

}
