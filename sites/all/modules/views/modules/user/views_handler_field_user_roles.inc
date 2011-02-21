<?php
// $Id: views_handler_field_user_roles.inc,v 1.2.4.2 2010/12/03 20:46:32 dereine Exp $
/**
 * Field handler to provide a list of roles.
 */
class views_handler_field_user_roles extends views_handler_field_prerender_list {
  function construct() {
    parent::construct();
    $this->additional_fields['uid'] = array('table' => 'users', 'field' => 'uid');
  }

  function query() {
    $this->add_additional_fields();
    $this->field_alias = $this->aliases['uid'];
  }

  function pre_render(&$values) {
    $uids = array();
    $this->items = array();

    foreach ($values as $result) {
      $uids[] = $result->{$this->aliases['uid']};
    }

    if ($uids) {
      $result = db_query("SELECT u.uid, u.rid, r.name FROM {role} r INNER JOIN {users_roles} u ON u.rid = r.rid WHERE u.uid IN (:uids) ORDER BY r.name",
        array(':uids' => $uids));
      foreach ($result as $role) {
        $this->items[$role->uid][$role->rid]['role'] = check_plain($role->name);
        $this->items[$role->uid][$role->rid]['rid'] = $role->rid;
      }
    }
  }

  function render_item($count, $item) {
    return $item['role'];
  }

  function document_self_tokens(&$tokens) {
    $tokens['[' . $this->options['id'] . '-role' . ']'] = t('The name of the role.');
    $tokens['[' . $this->options['id'] . '-rid' . ']'] = t('The role ID of the role.');
  }

  function add_self_tokens(&$tokens, $item) {
    $tokens['[' . $this->options['id'] . '-role' . ']'] = $item['role'];
    $tokens['[' . $this->options['id'] . '-rid' . ']'] = $item['rid'];
  }
}
