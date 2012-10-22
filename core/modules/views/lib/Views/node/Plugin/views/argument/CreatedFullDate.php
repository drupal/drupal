<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\argument\CreatedFullDate.
 */

namespace Views\node\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Date;

/**
 * Argument handler for a full date (CCYYMMDD)
 *
 * @Plugin(
 *   id = "node_created_fulldate",
 *   arg_format = "Ymd",
 *   format = "F j, Y",
 *   module = "node"
 * )
 */
class CreatedFullDate extends Date {

  /**
   * Overrides Drupal\views\Plugin\views\argument\Formula::get_formula().
   */
  function get_formula() {
    $this->formula = $this->getSQLFormat($this->definition['arg_format']);
    return parent::get_formula();
  }

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $created = $data->{$this->name_alias};
    return format_date(strtotime($created . " 00:00:00 UTC"), 'custom', $this->definition['format'], 'UTC');
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    return format_date(strtotime($this->argument . " 00:00:00 UTC"), 'custom', $this->definition['format'], 'UTC');
  }

}
