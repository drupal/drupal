<?php

namespace Drupal\datetime\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;

/**
 * Argument handler for a full date (CCYYMMDD).
 */
#[ViewsArgument(
  id: 'datetime_full_date',
)]
class FullDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Ymd';

}
