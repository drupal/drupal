<?php

namespace Drupal\datetime\Plugin\views\argument;

/**
 * Argument handler for a full date (CCYYMMDD).
 *
 * @ViewsArgument("datetime_full_date")
 */
class FullDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Ymd';

}
