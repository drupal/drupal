<?php

/**
 * @file
 * Definition of views_handler_field_user_permissions.
 */

/**
 * Field handler to provide a list of permissions.
 *
 * @ingroup views_field_handlers
 */
class views_handler_field_user_permissions extends views_handler_field_prerender_list {
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
      $uids[] = $this->get_value($result, NULL, TRUE);
    }

    if ($uids) {
      // Get a list of all the modules implementing a hook_permission() and sort by
      // display name.
      $module_info = system_get_info('module');
      $modules = array();
      foreach (module_implements('permission') as $module) {
        $modules[$module] = $module_info[$module]['name'];
      }
      asort($modules);

      $permissions = module_invoke_all('permission');

      $result = db_query("SELECT u.uid, u.rid, rp.permission FROM {role_permission} rp INNER JOIN {users_roles} u ON u.rid = rp.rid WHERE u.uid IN (:uids) AND rp.module IN (:modules) ORDER BY rp.permission",
        array(':uids' => $uids, ':modules' => array_keys($modules)));

      foreach ($result as $perm) {
        $this->items[$perm->uid][$perm->permission]['permission'] = $permissions[$perm->permission]['title'];
      }
    }
  }

  function render_item($count, $item) {
    return $item['permission'];
  }

  /*
  function document_self_tokens(&$tokens) {
    $tokens['[' . $this->options['id'] . '-role' . ']'] = t('The name of the role.');
    $tokens['[' . $this->options['id'] . '-rid' . ']'] = t('The role ID of the role.');
  }

  function add_self_tokens(&$tokens, $item) {
    $tokens['[' . $this->options['id'] . '-role' . ']'] = $item['role'];
    $tokens['[' . $this->options['id'] . '-rid' . ']'] = $item['rid'];
  }
  */
}
