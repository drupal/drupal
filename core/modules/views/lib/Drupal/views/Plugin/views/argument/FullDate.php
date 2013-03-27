<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument\FullDate.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\Component\Annotation\Plugin;

/**
 * Argument handler for a full date (CCYYMMDD)
 *
 * @Plugin(
 *   id = "date_fulldate",
 *   arg_format = "Ymd",
 *   format = "F j, Y",
 *   module = "views"
 * )
 */
class FullDate extends Date {

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
