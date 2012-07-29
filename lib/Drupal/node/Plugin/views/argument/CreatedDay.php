<?php

namespace Drupal\node\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugins\views\argument\Date;

/**
 * Argument handler for a day (DD)
 */

/**
 * @Plugin(
 *   plugin_id = "node_created_day"
 * )
 */
class CreatedDay extends Date {
  /**
   * Constructor implementation
   */
  function construct() {
    parent::construct();
    $this->formula = views_date_sql_extract('DAY', "***table***.$this->real_field");
    $this->format = 'j';
    $this->arg_format = 'd';
  }

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $day = str_pad($data->{$this->name_alias}, 2, '0', STR_PAD_LEFT);
    // strtotime respects server timezone, so we need to set the time fixed as utc time
    return format_date(strtotime("2005" . "05" . $day . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    $day = str_pad($this->argument, 2, '0', STR_PAD_LEFT);
    return format_date(strtotime("2005" . "05" . $day . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  function summary_argument($data) {
    // Make sure the argument contains leading zeroes.
    return str_pad($data->{$this->base_alias}, 2, '0', STR_PAD_LEFT);
  }
}
