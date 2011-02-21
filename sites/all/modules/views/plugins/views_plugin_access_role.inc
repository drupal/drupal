<?php
// $Id: views_plugin_access_role.inc,v 1.2.6.3 2010/03/10 21:03:19 merlinofchaos Exp $

/**
 * Access plugin that provides role-based access control.
 */
class views_plugin_access_role extends views_plugin_access {
  function access($account) {
    return views_check_roles(array_filter($this->options['role']), $account);
  }

  function get_access_callback() {
    return array('views_check_roles', array(array_filter($this->options['role'])));
  }

  function summary_title() {
    $count = count($this->options['role']);
    if ($count < 1) {
      return t('No role(s) selected');
    }
    elseif ($count > 1) {
      return t('Multiple roles');
    }
    else {
      $rids = views_ui_get_roles();
      $rid = array_shift($this->options['role']);
      return $rids[$rid];
    }
  }


  function option_definition() {
    $options = parent::option_definition();
    $options['role'] = array('default' => array());

    return $options;
  }

  function options_form(&$form, &$form_state) {
    $form['role'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Role'),
      '#default_value' => $this->options['role'],
      '#options' => views_ui_get_roles(),
      '#description' => t('Only the checked roles will be able to access this display. Note that users with "access all views" can see any view, regardless of role.'),
    );
  }

  function options_validate(&$form, &$form_state) {
    if (!array_filter($form_state['values']['access_options']['role'])) {
      form_error($form['role'], t('You must select at least one role if type is "by role"'));
    }
  }

  function options_submit(&$form, &$form_state) {
    // I hate checkboxes.
    $form_state['values']['access_options']['role'] = array_filter($form_state['values']['access_options']['role']);
  }
}



