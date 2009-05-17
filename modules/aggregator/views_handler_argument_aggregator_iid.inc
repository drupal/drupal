<?php

/**
 * @file
 * Definition of views_handler_argument_aggregator_iid.
 */

/**
 * Argument handler to accept an aggregator item id.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_aggregator_iid extends views_handler_argument_numeric {
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
