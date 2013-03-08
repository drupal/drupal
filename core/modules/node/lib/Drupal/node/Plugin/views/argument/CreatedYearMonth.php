<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument\CreatedYearMonth.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\Component\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Date;

/**
 * Argument handler for a year plus month (CCYYMM)
 *
 * @Plugin(
 *   id = "node_created_year_month",
 *   format = "F Y",
 *   arg_format = "Ym",
 *   module = "node"
 * )
 */
class CreatedYearMonth extends Date {

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $created = $data->{$this->name_alias};
    return format_date(strtotime($created . "15" . " 00:00:00 UTC"), 'custom', $this->definition['format'], 'UTC');
  }

  /**
   * Provide a link to the next level of the view
   */
  function title() {
    return format_date(strtotime($this->argument . "15" . " 00:00:00 UTC"), 'custom', $this->definition['format'], 'UTC');
  }

}
