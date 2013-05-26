<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument\Vid.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Component\Annotation\PluginID;

/**
 * Argument handler to accept a node revision id.
 *
 * @PluginID("node_vid")
 */
class Vid extends Numeric {

  // No constructor is necessary.

  /**
   * Override the behavior of title(). Get the title of the revision.
   */
  function title_query() {
    $titles = array();

    $results = db_select('node_field_revision', 'npr')
      ->fields('npr', array('vid', 'nid', 'title'))
      ->condition('npr.vid', $this->value)
      ->execute()
      ->fetchAllAssoc('vid', PDO::FETCH_ASSOC);
    $nids = array();
    foreach ($results as $result) {
      $nids[] = $result['nid'];
    }

    $nodes = node_load_multiple(array_unique($nids));

    foreach ($results as $result) {
      $nodes[$result['nid']]->set('title', $result['title']);
      $titles[] = check_plain($nodes[$result['nid']]->label());
    }

    return $titles;
  }

}
