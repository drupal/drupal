<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument\CreatedWeek.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\Component\Annotation\Plugin;
use Drupal\views\Plugin\views\argument\Date;

/**
 * Argument handler for a week.
 *
 * @Plugin(
 *   id = "node_created_week",
 *   arg_format = "W",
 *   module = "node"
 * )
 */
class CreatedWeek extends Date {

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $created = $data->{$this->name_alias};
    return t('Week @week', array('@week' => $created));
  }

}
