<?php

/**
 * @file
 * Provide node nid argument handler.
 */

/**
 * Argument handler to accept a node id.
 */
class views_handler_argument_node_nid extends views_handler_argument_numeric {
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
