<?php

/**
 * @file
 * Definition of Views\node\Plugin\views\argument\UidRevision.
 */

namespace Views\node\Plugin\views\argument;

use Views\user\Plugin\views\argument\Uid;
use Drupal\Core\Annotation\Plugin;

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
    $this->ensure_my_table();
    $placeholder = $this->placeholder();
    $this->query->add_where_expression(0, "$this->table_alias.uid = $placeholder OR ((SELECT COUNT(*) FROM {node_revision} nr WHERE nr.uid = $placeholder AND nr.nid = $this->table_alias.nid) > 0)", array($placeholder => $this->argument));
  }

}
