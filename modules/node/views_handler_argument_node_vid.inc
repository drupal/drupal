<?php

/**
 * @file
 * Provide node vid argument handler.
 */

/**
 * Argument handler to accept a node revision id.
 */
class views_handler_argument_node_vid extends views_handler_argument_numeric {
  // No constructor is necessary.

  /**
   * Override the behavior of title(). Get the title of the revision.
   */
  function title_query() {
    $titles = array();

    $result = db_query("SELECT n.title FROM {node_revision} n WHERE n.nid IN (:nids)", array(':nids' => $this->value));
    foreach ($result as $term) {
      $titles[] = check_plain($term->title);
    }
    return $titles;
  }
}
