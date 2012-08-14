<?php

/**
 * @file
 * Provide node tnid argument handler.
 */

namespace Views\translation\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept a node translation id.
 *
 * @ingroup views_argument_handlers
 */

/**
 * @Plugin(
 *   id = "node_tnid",
 *   module = "translation"
 * )
 */
class NodeTnid extends Numeric {
  /**
   * Override the behavior of title(). Get the title of the node.
   */
  function title_query() {
    $titles = array();

    $result = db_query("SELECT n.title FROM {node} n WHERE n.tnid IN (:tnids)", array(':tnids' => $this->value));
    foreach ($result as $term) {
      $titles[] = check_plain($term->title);
    }
    return $titles;
  }
}
