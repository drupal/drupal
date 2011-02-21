<?php
// $Id: views_handler_filter_node_access.inc,v 1.2.4.2 2010/08/26 09:41:55 dereine Exp $
/**
 * Filter by node_access records.
 */
class views_handler_filter_node_access extends views_handler_filter {
  function admin_summary() { }
  function operator_form(&$form, &$form_state) { }
  function can_expose() {
    return FALSE;
  }

  /**
   * See _node_access_where_sql() for a non-views query based implementation.
   */
  function query() {
    if (!user_access('administer nodes')) {
      $table = $this->ensure_my_table();
      $grants = db_or();
      foreach (node_access_grants('view') as $realm => $gids) {
        foreach ($gids as $gid) {
          $grants->condition(db_and()
            ->condition($table . '.gid', $gid)
            ->condition($table . '.realm', $realm)
          );
        }
      }

      $this->query->add_where('AND', $grants);
      $this->query->add_where('AND', $table . '.grant_view', 1, '>=');
    }
  }
}
