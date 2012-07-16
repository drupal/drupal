<?php

/**
 * @file
 * Definition of views_plugin_access_perm.
 */

/**
 * Access plugin that provides permission-based access control.
 *
 * @ingroup views_access_plugins
 */
class views_plugin_access_perm extends views_plugin_access {
  function access($account) {
    return views_check_perm($this->options['perm'], $account);
  }

  function get_access_callback() {
    return array('views_check_perm', array($this->options['perm']));
  }

  function summary_title() {
    $permissions = module_invoke_all('permission');
    if (isset($permissions[$this->options['perm']])) {
      return $permissions[$this->options['perm']]['title'];
    }

    return t($this->options['perm']);
  }


  function option_definition() {
    $options = parent::option_definition();
    $options['perm'] = array('default' => 'access content');

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $perms = array();
    $module_info = system_get_info('module');

    // Get list of permissions
    foreach (module_implements('permission') as $module) {
      $permissions = module_invoke($module, 'permission');
      foreach ($permissions as $name => $perm) {
        $perms[$module_info[$module]['name']][$name] = strip_tags($perm['title']);
      }
    }

    ksort($perms);

    $form['perm'] = array(
      '#type' => 'select',
      '#options' => $perms,
      '#title' => t('Permission'),
      '#default_value' => $this->options['perm'],
      '#description' => t('Only users with the selected permission flag will be able to access this display. Note that users with "access all views" can see any view, regardless of other permissions.'),
    );
  }
}
