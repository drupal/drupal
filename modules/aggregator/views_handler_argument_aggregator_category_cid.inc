<?php

/**
 * @file
 * Definition of views_handler_argument_aggregator_category_cid.
 */

/**
 * Argument handler to accept an aggregator category id.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_aggregator_category_cid extends views_handler_argument_numeric {
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
