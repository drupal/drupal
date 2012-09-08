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
    $this->ensureMyTable();

    $placeholder = $this->placeholder();

    $args = array_values($this->value);

    $this->query->add_where_expression($this->options['group'], "$this->tableAlias.uid IN($placeholder) OR
      ((SELECT COUNT(*) FROM {node_revision} nr WHERE nr.uid IN($placeholder) AND nr.nid = $this->tableAlias.nid) > 0)", array($placeholder => $args),
      $args);
  }

}
