<?php
// $Id: views_plugin_argument_default_node.inc,v 1.1.6.1 2010/01/28 22:44:36 dereine Exp $
/**
 * @file
 * Contains the node from URL argument default plugin.
 */

/**
 * Default argument plugin to extract a node via menu_get_object
 *
 * This plugin actually has no options so it odes not need to do a great deal.
 */
class views_plugin_argument_default_node extends views_plugin_argument_default {
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

