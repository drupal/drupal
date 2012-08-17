<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\argument\CreatedYear.
 */

namespace Views\node\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Date;

/**
 * Argument handler for a year (CCYY)
 *
 * @Plugin(
 *   id = "node_created_year",
 *   module = "node"
 * )
 */
class CreatedYear extends Date {

  /**
   * Constructor implementation
   */
  function construct() {
    parent::construct();
    $this->arg_format = 'Y';
    $this->formula = views_date_sql_extract('YEAR', "***table***.$this->real_field");
  }

}
