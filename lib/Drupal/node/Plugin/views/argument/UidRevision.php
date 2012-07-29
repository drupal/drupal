<?php

/**
 * @file
 * Defintion of views_handler_argument_node_uid_revision.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\user\Plugin\views\argument\UserUid;
use Drupal\Core\Annotation\Plugin;

/**
 * Filter handler to accept a user id to check for nodes that
 * user posted or created a revision on.
 */

/**
 * @Plugin(
 *   plugin_id = "node_uid_revision"
 * )
 */
class UidRevision extends UserUid {
  function query($group_by = FALSE) {
    $this->ensure_my_table();
    $placeholder = $this->placeholder();
    $this->query->add_where_expression(0, "$this->table_alias.uid = $placeholder OR ((SELECT COUNT(*) FROM {node_revision} nr WHERE nr.uid = $placeholder AND nr.nid = $this->table_alias.nid) > 0)", array($placeholder => $this->argument));
  }
}
