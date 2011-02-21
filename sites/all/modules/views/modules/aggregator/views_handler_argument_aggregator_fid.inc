<?php
// $Id: views_handler_argument_aggregator_fid.inc,v 1.1.6.2 2010/10/23 06:30:12 dereine Exp $

/**
 * Argument handler to accept an aggregator feed id.
 */
class views_handler_argument_aggregator_fid extends views_handler_argument_numeric {
  /**
   * Override the behavior of title(). Get the title of the feed.
   */
  function title_query() {
    $titles = array();

    $result = db_query("SELECT f.title FROM {aggregator_feed} f WHERE f.fid IN (:fids)", array(':fids' => $this->value));
    foreach ($result as $term) {
      $titles[] = check_plain($term->title);
    }
    return $titles;
  }
}
