<?php
/**
 * @file
 * Contains the book root from current node argument default plugin.
 */

/**
 * Default argument plugin to get the current node's book root.
 */
class views_plugin_argument_default_book_root extends views_plugin_argument_default_node {
  function get_argument() {
    // Use the argument_default_node plugin to get the nid argument.
    $nid = parent::get_argument();
    if (!empty($nid)) {
      $node = node_load($nid);
      if (isset($node->book['bid'])) {
        return $node->book['bid'];
      }
    }
  }
}
