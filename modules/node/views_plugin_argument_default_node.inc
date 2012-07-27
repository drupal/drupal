<?php

/**
 * @file
 * Contains the node from URL argument default plugin.
 */

use Drupal\views\Plugins\views\argument_default\ArgumentDefaultPluginBase;

/**
 * Default argument plugin to extract a node via menu_get_object
 *
 * This plugin actually has no options so it odes not need to do a great deal.
 */
class views_plugin_argument_default_node extends ArgumentDefaultPluginBase {
  function get_argument() {
    foreach (range(1, 3) as $i) {
      $node = menu_get_object('node', $i);
      if (!empty($node)) {
        return $node->nid;
      }
    }

    if (arg(0) == 'node' && is_numeric(arg(1))) {
      return arg(1);
    }
  }
}
