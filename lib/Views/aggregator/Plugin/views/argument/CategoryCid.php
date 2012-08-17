<?php

/**
 * @file
 * Definition of Views\aggregator\Plugin\views\argument\CategoryCid.
 */

namespace Views\aggregator\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept an aggregator category id.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "aggregator_category_cid",
 *   module = "aggregator"
 * )
 */
class CategoryCid extends Numeric {

  /**
   * Override the behavior of title(). Get the title of the category.
   */
  function title_query() {
    $titles = array();

    $result = db_query("SELECT c.title FROM {aggregator_category} c WHERE c.cid IN (:cid)", array(':cid' => $this->value));
    foreach ($result as $term) {
      $titles[] = check_plain($term->title);
    }
    return $titles;
  }

}
