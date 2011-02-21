<?php
// $Id: views_handler_argument_aggregator_category_cid.inc,v 1.1.6.2 2009/11/13 12:36:05 dereine Exp $

/**
 * Argument handler to accept an aggregator category id.
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
