<?php
// $Id: views_handler_argument_users_roles_rid.inc,v 1.1.6.1 2009/11/02 22:01:27 merlinofchaos Exp $
/**
 * Allow role ID(s) as argument
 */
class views_handler_argument_users_roles_rid extends views_handler_argument_many_to_one {
  function title_query() {
    $titles = array();

    $result = db_query("SELECT name FROM {role} WHERE rid IN (:rids)", array(':rids' => $this->value));
    foreach ($result as $term) {
      $titles[] = check_plain($term->name);
    }
    return $titles;
  }
}

