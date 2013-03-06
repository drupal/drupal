<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\argument\UidRevision.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\user\Plugin\views\argument\Uid;
use Drupal\Component\Annotation\Plugin;

/**
 * Filter handler to accept a user id to check for nodes that
 * user posted or created a revision on.
 *
 * @Plugin(
 *   id = "node_uid_revision",
 *   module = "node"
 * )
 */
class UidRevision extends Uid {

  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $placeholder = $this->placeholder();
    $this->query->add_where_expression(0, "$this->tableAlias.uid = $placeholder OR ((SELECT COUNT(*) FROM {node_revision} nr WHERE nr.uid = $placeholder AND nr.nid = $this->tableAlias.nid) > 0)", array($placeholder => $this->argument));
  }

}
