<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument\YearDate.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\Component\Annotation\PluginID;

/**
 * Argument handler for a year (CCYY)
 *
 * @PluginID("date_year")
 */
class YearDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'Y';

}
