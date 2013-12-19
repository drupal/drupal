<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument_default\Node.
 */

namespace Drupal\node\Plugin\views\argument_default;

use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument plugin to extract a node via menu_get_object
 *
 * This plugin actually has no options so it odes not need to do a great deal.
 *
 * @ViewsArgumentDefault(
 *   id = "node",
 *   title = @Translation("Content ID from URL")
 * )
 */
class Node extends ArgumentDefaultPluginBase {

  public function getArgument() {
    foreach (range(1, 3) as $i) {
      $node = menu_get_object('node', $i);
      if (!empty($node)) {
        return $node->id();
      }
    }

    if (arg(0) == 'node' && is_numeric(arg(1))) {
      return arg(1);
    }
  }

}
