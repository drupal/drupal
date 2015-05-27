<?php

/**
 * @file
 * Contains \Drupal\datetime\Plugin\views\argument\YearDate.
 */

namespace Drupal\datetime\Plugin\views\argument;

/**
 * Argument handler for a year.
 *
 * @ViewsArgument("datetime_year")
 */
class YearDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Y';

}
