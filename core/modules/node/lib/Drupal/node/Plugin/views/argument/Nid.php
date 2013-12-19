<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument\Nid.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\Numeric;

/**
 * Argument handler to accept a node id.
 *
 * @PluginID("node_nid")
 */
class Nid extends Numeric {

  /**
   * Override the behavior of title(). Get the title of the node.
   */
  public function titleQuery() {
    $titles = array();

    $nodes = node_load_multiple($this->value);
    foreach ($nodes as $node) {
      $titles[] = check_plain($node->label());
    }
    return $titles;
  }

}
