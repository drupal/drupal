<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument\CreatedYear.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\Component\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Date;

/**
 * Argument handler for a year (CCYY)
 *
 * @Plugin(
 *   id = "node_created_year",
 *   arg_format = "Y",
 *   module = "node"
 * )
 */
class CreatedYear extends Date {

  /**
   * Overrides Drupal\views\Plugin\views\argument\Formula::get_formula().
   */
  function get_formula() {
    $this->formula = $this->extractSQL('YEAR');
    return parent::get_formula();
  }

}
