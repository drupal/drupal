<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\filter\UidRevision.
 */

namespace Views\node\Plugin\views\filter;

use Views\user\Plugin\views\filter\Name;
use Drupal\Core\Annotation\Plugin;

/**
 * Filter handler to check for revisions a certain user has created.
 *
 * @ingroup views_filter_handlers
 *
 * @Plugin(
 *   id = "node_uid_revision",
 *   module = "node"
 * )
 */
class UidRevision extends Name {

  public function query($group_by = FALSE) {
    $this->ensure_my_table();

    $placeholder = $this->placeholder();

    $args = array_values($this->value);

    $this->query->add_where_expression($this->options['group'], "$this->table_alias.uid IN($placeholder) " . $condition . " OR
      ((SELECT COUNT(*) FROM {node_revision} nr WHERE nr.uid IN($placeholder) AND nr.nid = $this->table_alias.nid) > 0)", array($placeholder => $args),
      $args);
  }

}
