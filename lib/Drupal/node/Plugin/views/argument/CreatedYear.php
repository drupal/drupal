<?php

namespace Drupal\node\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugins\views\argument\Date;

/**
 * Argument handler for a year (CCYY)
 */

/**
 * @Plugin(
 *   plugin_id = "node_created_year"
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
