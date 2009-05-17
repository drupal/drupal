<?php

/**
 * @file
 * Definition of views_handler_filter_node_uid_revision.
 */

/**
 * Filter handler to check for revisions a certain user has created.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_node_uid_revision extends views_handler_filter_user_name {
  function query($group_by = FALSE) {
    $this->ensure_my_table();

    $placeholder = $this->placeholder();

    $args = array_values($this->value);

    $this->query->add_where_expression($this->options['group'], "$this->table_alias.uid IN($placeholder) " . $condition . " OR
      ((SELECT COUNT(*) FROM {node_revision} nr WHERE nr.uid IN($placeholder) AND nr.nid = $this->table_alias.nid) > 0)", array($placeholder => $args),
      $args);
  }
}
