<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\argument\WeekDate.
 */

namespace Drupal\views\Plugin\views\argument;

use Drupal\Component\Annotation\Plugin;

/**
 * Argument handler for a week.
 *
 * @Plugin(
 *   id = "date_week",
 *   arg_format = "W",
 *   module = "views"
 * )
 */
class WeekDate extends Date {

  /**
   * Provide a link to the next level of the view
   */
  function summary_name($data) {
    $created = $data->{$this->name_alias};
    return t('Week @week', array('@week' => $created));
  }

}
