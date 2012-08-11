<?php

/**
 * @file
 * Definition of views_handler_argument_aggregator_iid.
 */

namespace Views\aggregator\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept an aggregator item id.
 *
 * @ingroup views_argument_handlers
 */

/**
 * @Plugin(
 *   id = "aggregator_iid"
 * )
 */
class Iid extends Numeric {
  /**
   * Override the behavior of title(). Get the title of the category.
   */
  function title_query() {
    $titles = array();
    $placeholders = implode(', ', array_fill(0, sizeof($this->value), '%d'));

    $result = db_select('aggregator_item')
      ->condition('iid', $this->value, 'IN')
      ->fields(array('title'))
      ->execute();
    foreach ($result as $term) {
      $titles[] = check_plain($term->title);
    }
    return $titles;
  }
}
