<?php

/**
 * @file
 * Definition of views_handler_filter_aggregator_category_cid.
 */

namespace Views\aggregator\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Annotation\Plugin;

/**
 * Filter by aggregator category cid
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "aggregator_category_cid",
 *   module = "aggregator"
 * )
 */
class CategoryCid extends InOperator {

  function get_value_options() {
    if (isset($this->value_options)) {
      return;
    }

    $this->value_options = array();

    $result = db_query('SELECT * FROM {aggregator_category} ORDER BY title');
    foreach ($result as $category) {
      $this->value_options[$category->cid] = $category->title;
    }
  }

}
