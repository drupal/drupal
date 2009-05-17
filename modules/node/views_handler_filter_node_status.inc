<?php

/**
 * @file
 * Definition of views_handler_filter_node_status.
 */

/**
 * Filter by published status.
 *
 * @ingroup views_filter_handlers
 */
class views_handler_filter_node_status extends views_handler_filter {
  function admin_summary() { }
  function operator_form(&$form, &$form_state) { }
  function can_expose() { return FALSE; }

  function query() {
    $table = $this->ensure_my_table();
    $this->query->add_where_expression($this->options['group'], "$table.status = 1 OR ($table.uid = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0 AND ***VIEW_OWN_UNPUBLISHED_NODES*** = 1) OR ***BYPASS_NODE_ACCESS*** = 1");
  }
}
