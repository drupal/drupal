<?php

/**
 * @file
 * Defintion of views_handler_argument_node_uid_revision.
 */

/**
 * Filter handler to accept a user id to check for nodes that
 * user posted or created a revision on.
 */
class views_handler_argument_node_uid_revision extends views_handler_argument_comment_user_uid {
  function query($group_by = FALSE) {
    $this->ensure_my_table();
    $placeholder = $this->placeholder();
    $this->query->add_where_expression(0, "$this->table_alias.uid = $placeholder OR ((SELECT COUNT(*) FROM {node_revision} nr WHERE nr.uid = $placeholder AND nr.nid = $this->table_alias.nid) > 0)", array($placeholder => $this->argument));
  }
}
