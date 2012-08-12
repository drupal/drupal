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

    $results = db_query("SELECT nr.vid, nr.nid, nr.title FROM {node_revision} nr WHERE nr.vid IN (:vids)", array(':vids' => $this->value))->fetchAllAssoc('vid', PDO::FETCH_ASSOC);

    $nids = array();
    foreach ($results as $result) {
      $nids[] = $result['nid'];
    }

    $nodes = node_load_multiple(array_unique($nids));

    foreach ($results as $result) {
      $nodes[$result['nid']]->set('title', $result['title']);
      $titles[] = check_plain($nodes[$result['nid']]->label());
    }

    return $titles;
  }
}
