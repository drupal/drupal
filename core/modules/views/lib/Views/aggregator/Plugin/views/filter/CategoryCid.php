<?php

/**
 * @file
 * Definition of Views\aggregator\Plugin\views\filter\CategoryCid.
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
    // Uses db_query() rather than db_select() because the query is static and
    // does not include any variables.
    $result = db_query('SELECT * FROM {aggregator_category} ORDER BY title');
    foreach ($result as $category) {
      $this->value_options[$category->cid] = $category->title;
    }
  }

}
