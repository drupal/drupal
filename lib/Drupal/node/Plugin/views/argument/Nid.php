<?php

/**
 * @file
 * Provide node nid argument handler.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\views\Plugins\views\argument\Numeric;
use Drupal\Core\Annotation\Plugin;

/**
 * Argument handler to accept a node id.
 */

/**
 * @Plugin(
 *   plugin_id = "node_nid"
 * )
 */
class Nid extends Numeric {
  /**
   * Override the behavior of title(). Get the title of the node.
   */
  function title_query() {
    $titles = array();

    $nodes = node_load_multiple($this->value);
    foreach ($nodes as $node) {
      $titles[] = check_plain($node->label());
    }
    return $titles;
  }
}
