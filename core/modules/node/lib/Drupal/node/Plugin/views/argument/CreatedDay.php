<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument\CreatedDay.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\Component\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Date;

/**
 * Argument handler for a day (DD)
 *
 * @Plugin(
 *   id = "node_created_day",
 *   arg_format = "d",
 *   format = "j",
 *   module = "node"
 * )
 */
class CreatedDay extends Date {

  /**
   * Overrides Drupal\views\Plugin\views\argument\Formula::get_formula().
   */
  function get_formula() {
    $this->formula = $this->extractSQL('DAY');
    return parent::get_formula();
  }

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $day = str_pad($data->{$this->name_alias}, 2, '0', STR_PAD_LEFT);
    // strtotime respects server timezone, so we need to set the time fixed as utc time
    return format_date(strtotime("2005" . "05" . $day . " 00:00:00 UTC"), 'custom', $this->definition['format'], 'UTC');
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    $day = str_pad($this->argument, 2, '0', STR_PAD_LEFT);
    return format_date(strtotime("2005" . "05" . $day . " 00:00:00 UTC"), 'custom', $this->definition['format'], 'UTC');
  }

  function summary_argument($data) {
    // Make sure the argument contains leading zeroes.
    return str_pad($data->{$this->base_alias}, 2, '0', STR_PAD_LEFT);
  }

}
