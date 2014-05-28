<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument\YearDate.
 */

namespace Drupal\views\Plugin\views\argument;

/**
 * Argument handler for a year (CCYY)
 *
 * @ViewsArgument("date_year")
 */
class YearDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Y';

}
