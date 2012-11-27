<?php

/**
 * @file
 * Admin page callbacks for the Action module.
 */

/**
 * Menu callback; Displays an overview of available and configured actions.
 */
function action_admin_manage() {
  action_synchronize();
  $actions = action_list();
  $actions_map = action_actions_map($actions);
  $options = array();
  $unconfigurable = array();

  foreach ($actions_map as $key => $array) {
    if ($array['configurable']) {
      $options[$key] = $array['label'] . '...';
    }
    else {
      $unconfigurable[] = $array;
    }
  }

  $row = array();
  $instances_present = db_query("SELECT aid FROM {actions} WHERE parameters <> ''")->fetchField();
  $header = array(
    array('data' => t('Action type'), 'field' => 'type'),
    array('data' => t('Label'), 'field' => 'label'),
    $instances_present ? t('Operations') : '',
  );
  $query = db_select('actions')
    ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
    ->extend('Drupal\Core\Database\Query\TableSortExtender');
  $result = $query
    ->fields('actions')
    ->limit(50)
    ->orderByHeader($header)
    ->execute();

  foreach ($result as $action) {
    $row = array();
    $row[] = $action->type;
    $row[] = check_plain($action->label);
    $links = array();
    if ($action->parameters) {
      $links['configure'] = array(
        'title' => t('configure'),
        'href' => "admin/config/system/actions/configure/$action->aid",
      );
      $links['delete'] = array(
        'title' => t('delete'),
        'href' => "admin/config/system/actions/delete/$action->aid",
      );
    }
    $row[] = array(
      'data' => array(
        '#type' => 'operations',
        '#links' => $links,
      ),
    );

    $rows[] = $row;
  }

  if ($rows) {
    $pager = theme('pager');
    if (!empty($pager)) {
      $rows[] = array(array('data' => $pager, 'colspan' => '3'));
    }
    $build['action_header'] = array('#markup' => '<h3>' . t('Available actions:') . '</h3>');
    $build['action_table'] = array('#markup' => theme('table', array('header' => $header, 'rows' => $rows)));
  }

  if ($actions_map) {
    $build['action_admin_manage_form'] = drupal_get_form('action_admin_manage_form', $options);
  }

  return $build;
}

/**
 * Define the form for the actions overview page.
 *
 * @param $form_state
 *   An associative array containing the current state of the form; not used.
 * @param $options
 *   An array of configurable actions.
 * @return
 *   Form definition.
 *
 * @ingroup forms
 * @see action_admin_manage_form_submit()
 */
function action_admin_manage_form($form, &$form_state, $options = array()) {
  $form['parent'] = array(
    '#type' => 'details',
    '#title' => t('Create an advanced action'),
    '#attributes' => array('class' => array('container-inline')),
  );
  $form['parent']['action'] = array(
    '#type' => 'select',
    '#title' => t('Action'),
    '#title_display' => 'invisible',
    '#options' => $options,
    '#empty_option' => t('Choose an advanced action'),
  );
  $form['parent']['actions'] = array('#type' => 'actions');
  $form['parent']['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Create'),
  );
  return $form;
}

/**
 * Form submission handler for action_admin_manage_form().
 */
function action_admin_manage_form_submit($form, &$form_state) {
  if ($form_state['values']['action']) {
    $form_state['redirect'] = 'admin/config/system/actions/configure/' . $form_state['values']['action'];
  }
}

/**
 * Form constructor for the configuration of a single action.
 *
 * We provide the "Description" field. The rest of the form is provided by the
 * action. We then provide the Save button. Because we are combining unknown
 * form elements with the action configuration form, we use an 'action_' prefix
 * on our elements.
 *
 * @param $action
 *   Hash of an action ID or an integer. If it is a hash, we are
 *   creating a new instance. If it is an integer, we are editing an existing
 *   instance.
 *
 * @see action_admin_configure_validate()
 * @see action_admin_configure_submit()
 * @ingroup forms
 */
function action_admin_configure($form, &$form_state, $action = NULL) {
  if ($action === NULL) {
    drupal_goto('admin/config/system/actions');
  }

  $actions_map = action_actions_map(action_list());
  $edit = array();

  // Numeric action denotes saved instance of a configurable action.
  if (is_numeric($action)) {
    $aid = $action;
    // Load stored parameter values from database.
    $data = db_query("SELECT * FROM {actions} WHERE aid = :aid", array(':aid' => $aid))->fetch();
    $edit['action_label'] = $data->label;
    $edit['action_type'] = $data->type;
    $function = $data->callback;
    $action = drupal_hash_base64($data->callback);
    $params = unserialize($data->parameters);
    if ($params) {
      foreach ($params as $name => $val) {
        $edit[$name] = $val;
      }
    }
  }
  // Otherwise, we are creating a new action instance.
  else {
    $function = $actions_map[$action]['callback'];
    $edit['action_label'] = $actions_map[$action]['label'];
    $edit['action_type'] = $actions_map[$action]['type'];
  }

  $form['action_label'] = array(
    '#type' => 'textfield',
    '#title' => t('Label'),
    '#default_value' => $edit['action_label'],
    '#maxlength' => '255',
    '#description' => t('A unique label for this advanced action. This label will be displayed in the interface of modules that integrate with actions.'),
    '#weight' => -10
  );
  $action_form = $function . '_form';
  $form = array_merge($form, $action_form($edit));
  $form['action_type'] = array(
    '#type' => 'value',
    '#value' => $edit['action_type'],
  );
  $form['action_action'] = array(
    '#type' => 'hidden',
    '#value' => $action,
  );
  // $aid is set when configuring an existing action instance.
  if (isset($aid)) {
    $form['action_aid'] = array(
      '#type' => 'hidden',
      '#value' => $aid,
    );
  }
  $form['action_configured'] = array(
    '#type' => 'hidden',
    '#value' => '1',
  );
  $form['actions'] = array('#type' => 'actions');
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
    '#weight' => 13
  );

  return $form;
}

/**
 * Form validation handler for action_admin_configure().
 *
 * @see action_admin_configure_submit()
 */
function action_admin_configure_validate($form, &$form_state) {
  $function = action_function_lookup($form_state['values']['action_action']) . '_validate';
  // Hand off validation to the action.
  if (function_exists($function)) {
    $function($form, $form_state);
  }
}

/**
 * Form submission handler for action_admin_configure().
 *
 * @see action_admin_configure_validate()
 */
function action_admin_configure_submit($form, &$form_state) {
  $function = action_function_lookup($form_state['values']['action_action']);
  $submit_function = $function . '_submit';

  // Action will return keyed array of values to store.
  $params = $submit_function($form, $form_state);
  $aid = isset($form_state['values']['action_aid']) ? $form_state['values']['action_aid'] : NULL;

  action_save($function, $form_state['values']['action_type'], $params, $form_state['values']['action_label'], $aid);
  drupal_set_message(t('The action has been successfully saved.'));

  $form_state['redirect'] = 'admin/config/system/actions/manage';
}

/**
 * Creates the form for confirmation of deleting an action.
 *
 * @see action_admin_delete_form_submit()
 * @ingroup forms
 */
function action_admin_delete_form($form, &$form_state, $action) {
  $form['aid'] = array(
    '#type' => 'hidden',
    '#value' => $action->aid,
  );
  return confirm_form($form,
    t('Are you sure you want to delete the action %action?', array('%action' => $action->label)),
    'admin/config/system/actions/manage',
    t('This cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission handler for action_admin_delete_form().
 */
function action_admin_delete_form_submit($form, &$form_state) {
  $aid = $form_state['values']['aid'];
  $action = action_load($aid);
  action_delete($aid);
  watchdog('user', 'Deleted action %aid (%action)', array('%aid' => $aid, '%action' => $action->label));
  drupal_set_message(t('Action %action was deleted', array('%action' => $action->label)));
  $form_state['redirect'] = 'admin/config/system/actions/manage';
}

/**
 * Post-deletion operations for deleting action orphans.
 *
 * @param $orphaned
 *   An array of orphaned actions.
 */
function action_admin_delete_orphans_post($orphaned) {
  foreach ($orphaned as $callback) {
    drupal_set_message(t("Deleted orphaned action (%action).", array('%action' => $callback)));
  }
}

/**
 * Removes actions that are in the database but not supported by any enabled module.
 */
function action_admin_remove_orphans() {
  action_synchronize(TRUE);
  drupal_goto('admin/config/system/actions/manage');
}
