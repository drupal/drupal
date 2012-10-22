<?php

/**
 * @file
 * Definition of Views\aggregator\Plugin\views\argument\Fid.
 */

namespace Views\aggregator\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept an aggregator feed id.
 *
 * @ingroup views_argument_handlers
 *
 * @Plugin(
 *   id = "aggregator_fid",
 *   module = "aggregator"
 * )
 */
class Fid extends Numeric {

  /**
   * Override the behavior of title(). Get the title of the feed.
   */
  function title_query() {
    $titles = array();

    $result = db_query("SELECT f.title FROM {aggregator_feed} f WHERE f.fid IN (:fids)", array(':fids' => $this->value));
    $query = db_select('aggregator_feed', 'f');
    $query->addField('f', 'title');
    $query->condition('f.fid', $this->value);
    $result = $query->execute();
    foreach ($result as $term) {
      $titles[] = check_plain($term->title);
    }
    return $titles;
  }

}
