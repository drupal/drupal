<?php

namespace Drupal\node\Plugin\views\argument;

use Drupal\Core\Annotation\Plugin;
use Drupal\views\Plugins\views\argument\Date;

/**
 * Argument handler for a full date (CCYYMMDD)
 */

/**
 * @Plugin(
 *   plugin_id = "node_created_fulldate"
 * )
 */
class CreatedFullDate extends Date {
  /**
   * Constructor implementation
   */
  function construct() {
    parent::construct();
    $this->format = 'F j, Y';
    $this->arg_format = 'Ymd';
    $this->formula = views_date_sql_format($this->arg_format, "***table***.$this->real_field");
  }

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $created = $data->{$this->name_alias};
    return format_date(strtotime($created . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    return format_date(strtotime($this->argument . " 00:00:00 UTC"), 'custom', $this->format, 'UTC');
  }
}
