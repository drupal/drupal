<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument\CreatedFullDate.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\Component\Annotation\Plugin;
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
