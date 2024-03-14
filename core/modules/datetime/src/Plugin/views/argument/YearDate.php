<?php

namespace Drupal\datetime\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;

/**
 * Argument handler for a year.
  */
#[ViewsArgument(
  id: 'datetime_year',
)]
class YearDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Y';

}
