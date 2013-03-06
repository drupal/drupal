<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument\CreatedMonth.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\Component\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Date;

/**
 * Argument handler for a month (MM)
 *
 * @Plugin(
 *   id = "node_created_month",
 *   arg_format = "m",
 *   format = "F",
 *   module = "node"
 * )
 */
class CreatedMonth extends Date {

  /**
   * Overrides Drupal\views\Plugin\views\argument\Formula::get_formula().
   */
  function get_formula() {
    $this->formula = $this->extractSQL('MONTH');
    return parent::get_formula();
  }

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $month = str_pad($data->{$this->name_alias}, 2, '0', STR_PAD_LEFT);
    return format_date(strtotime("2005" . $month . "15" . " 00:00:00 UTC" ), 'custom', $this->definition['format'], 'UTC');
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    $month = str_pad($this->argument, 2, '0', STR_PAD_LEFT);
    return format_date(strtotime("2005" . $month . "15" . " 00:00:00 UTC"), 'custom', $this->definition['format'], 'UTC');
  }

  function summary_argument($data) {
    // Make sure the argument contains leading zeroes.
    return str_pad($data->{$this->base_alias}, 2, '0', STR_PAD_LEFT);
  }

}
