<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument\YearDate.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\Component\Annotation\Plugin;

/**
 * Argument handler for a year (CCYY)
 *
 * @Plugin(
 *   id = "date_year",
 *   module = "views"
 * )
 */
class YearDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Y';

}
