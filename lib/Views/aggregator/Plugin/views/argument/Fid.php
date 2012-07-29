<?php

/**
 * @file
 * Definition of views_handler_argument_aggregator_fid.
 */

namespace Views\aggregator\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept an aggregator feed id.
 *
 * @ingroup views_argument_handlers
 */

/**
 * @Plugin(
 *   plugin_id = "aggregator_fid"
 * )
 */
class Fid extends Numeric {
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
