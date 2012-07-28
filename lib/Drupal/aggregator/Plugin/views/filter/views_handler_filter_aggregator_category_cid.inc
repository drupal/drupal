<?php

/**
 * @file
 * Definition of views_handler_filter_aggregator_category_cid.
 */

use Drupal\views\Plugins\views\filter\InOperator;

/**
 * Filter by aggregator category cid
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_aggregator_category_cid extends InOperator {
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
