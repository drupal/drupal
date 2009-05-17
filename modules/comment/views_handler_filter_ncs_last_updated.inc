<?php

/**
 * @file
 * Definition of views_handler_filter_ncs_last_updated.
 */

/**
 * Filter handler for the newer of last comment / node updated.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_ncs_last_updated extends views_handler_filter_date {
  function query() {
    $this->ensure_my_table();
    $this->node_table = $this->query->ensure_table('node', $this->relationship);

    $field = "GREATEST(" . $this->node_table . ".changed, " . $this->table_alias . ".last_comment_timestamp)";

    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($field);
    }
  }
}
