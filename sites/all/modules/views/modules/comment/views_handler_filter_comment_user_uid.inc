<?php
// $Id: views_handler_filter_comment_user_uid.inc,v 1.2.4.2 2009/11/13 12:36:06 dereine Exp $

/**
 * Filter handler to accept a user id to check for nodes that user posted or
 * commented on.
 */
class views_handler_filter_comment_user_uid extends views_handler_filter_user_name {
  function query() {
    $this->ensure_my_table();

    $subselect = db_select('comments', 'c');
    $subselect->addField('c', 'cid');
    $subselect->condition('uid', $this->value, $this->operator);
    $subselect->condition('nid', "$this->table_alias.nid");
    $subselect = $subselect->countQuery();

    $condition = db_or()
      ->condition("$this->table_alias.uid", $this->value, $this->operator)
      ->condition(0, $subselect, '<');

    $this->query->add_where(0, $condition);
  }
}
