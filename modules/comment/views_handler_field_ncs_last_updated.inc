<?php

/**
 * @file
 * Definition of views_handler_field_ncs_last_updated.
 */
/**
 * Field handler to display the newer of last comment / node updated.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_ncs_last_updated extends views_handler_field_date {
  function query() {
    $this->ensure_my_table();
    $this->node_table = $this->query->ensure_table('node', $this->relationship);
    $this->field_alias = $this->query->add_field(NULL, "GREATEST(" . $this->node_table . ".changed, " . $this->table_alias . ".last_comment_timestamp)", $this->table_alias . '_' . $this->field);
  }
}
