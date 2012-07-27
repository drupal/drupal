<?php

/**
 * @file
 * Definition of views_handler_argument_aggregator_fid.
 */

use Drupal\views\Plugins\views\argument\Numeric;

/**
 * Argument handler to accept an aggregator feed id.
 *
 * @ingroup views_argument_handlers
 */
class views_handler_argument_aggregator_fid extends Numeric {
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
