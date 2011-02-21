<?php
// $Id: views_handler_argument_comment_user_uid.inc,v 1.2.4.4 2010/12/09 23:36:00 dereine Exp $

/**
 * Argument handler to accept a user id to check for nodes that
 * user posted or commented on.
 */
class views_handler_argument_comment_user_uid extends views_handler_argument {
  function title() {
    if (!$this->argument) {
      $title = variable_get('anonymous', t('Anonymous'));
    }
    else {
      $title = db_query('SELECT u.name FROM {users} u WHERE u.uid = :uid', array(':uid' => $this->argument))->fetchField();
    }
    if (empty($title)) {
      return t('No user');
    }

    return check_plain($title);
  }

  function default_actions($which = NULL) {
    // Disallow summary views on this argument.
    if (!$which) {
      $actions = parent::default_actions();
      unset($actions['summary asc']);
      unset($actions['summary desc']);
      return $actions;
    }

    if ($which != 'summary asc' && $which != 'summary desc') {
      return parent::default_actions($which);
    }
  }

  function query() {
    $this->ensure_my_table();

    $nid_alias = $this->query->add_field('node', 'nid');

    $subselect = db_select('comment', 'c');
    $subselect->addField('c', 'cid');
    // @TODO: Figure out why condition/placeholders doesn't work here.
    $subselect->where("c.uid =  $this->argument");
    $subselect->where("c.nid = $nid_alias");
    $subselect = $subselect->countQuery();
    $subselect->preExecute();

    $condition = db_or()
      ->condition("$this->table_alias.uid", $this->argument, '=')
      ->where("0 < (" . (string) $subselect . ")");

    $this->query->add_where(0, $condition);
  }
}
