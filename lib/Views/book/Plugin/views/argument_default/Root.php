<?php

/**
 * @file
 * Definition of Views\book\Plugin\views\argument_default\Root.
 */

namespace Views\book\Plugin\views\argument_default;

use Views\node\Plugin\views\argument_default\Node;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Default argument plugin to get the current node's book root.
 *
 * @Plugin(
 *   id = "book_root",
 *   module = "book",
 *   title = @Translation("Book root from current node")
 * )
 */
class Root extends Node {

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
