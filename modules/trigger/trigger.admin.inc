<?php
// $Id: trigger.admin.inc,v 1.5 2008/01/08 10:35:43 goba Exp $

/**
 * @file
 * Admin page callbacks for the trigger module.
 */

/**
 * Build the form that allows users to assign actions to hooks.
 *
 * @param $type
 *   Name of hook.
 * @return
 *   HTML form.
 */
function trigger_assign($type = NULL) {
  // If no type is specified we default to node actions, since they
  // are the most common.
  if (!isset($type)) {
    drupal_goto('admin/build/trigger/node');
  }
  if ($type == 'node') {
    $type = 'nodeapi';
  }

  $output = '';
  $hooks = module_invoke_all('hook_info');
  foreach ($hooks as $module => $hook) {
    if (isset($hook[$type])) {
      foreach ($hook[$type] as $op => $description) {
        $form_id = 'trigger_'. $type .'_'. $op .'_assign_form';
        $output .= drupal_get_form($form_id, $type, $op, $description['runs when']);
      }
    }
  }
  return $output;
}

/**
 * Confirm removal of an assigned action.
 *
 * @param $hook
 * @param $op
 * @param $aid
 *   The action ID.
 * @ingroup forms
 * @see trigger_unassign_submit()
 */
function trigger_unassign($form_state, $hook = NULL, $op = NULL, $aid = NULL) {
  if (!($hook && $op && $aid)) {
    drupal_goto('admin/build/trigger/assign');
  }

  $form['hook'] = array(
    '#type' => 'value',
    '#value' => $hook,
  );
  $form['operation'] = array(
    '#type' => 'value',
    '#value' => $op,
  );
  $form['aid'] = array(
    '#type' => 'value',
    '#value' => $aid,
  );

  $action = actions_function_lookup($aid);
  $actions = actions_get_all_actions();

  $destination = 'admin/build/trigger/'. ($hook == 'nodeapi' ? 'node' : $hook);

  return confirm_form($form,
    t('Are you sure you want to unassign the action %title?', array('%title' => $actions[$action]['description'])),
    $destination,
    t('You can assign it again later if you wish.'),
    t('Unassign'), t('Cancel')
  );
}

function trigger_unassign_submit($form, &$form_state) {
  $form_values = $form_state['values'];
  if ($form_values['confirm'] == 1) {
    $aid = actions_function_lookup($form_values['aid']);
    db_query("DELETE FROM {trigger_assignments} WHERE hook = '%s' AND op = '%s' AND aid = '%s'", $form_values['hook'], $form_values['operation'], $aid);
    $actions = actions_get_all_actions();
    watchdog('actions', 'Action %action has been unassigned.',  array('%action' => check_plain($actions[$aid]['description'])));
    drupal_set_message(t('Action %action has been unassigned.', array('%action' => $actions[$aid]['description'])));
    $hook = $form_values['hook'] == 'nodeapi' ? 'node' : $form_values['hook'];
    $form_state['redirect'] = 'admin/build/trigger/'. $hook;
  }
  else {
    drupal_goto('admin/build/trigger');
  }
}

/**
 * Create the form definition for assigning an action to a hook-op combination.
 *
 * @param $form_state
 *   Information about the current form.
 * @param $hook
 *   The name of the hook, e.g., 'nodeapi'.
 * @param $op
 *   The name of the hook operation, e.g., 'insert'.
 * @param $description
 *   A plain English description of what this hook operation does.
 * @return
 *
 * @ingoup forms
 * @see trigger_assign_form_validate()
 * @see trigger_assign_form_submit()
 */
function trigger_assign_form($form_state, $hook, $op, $description) {
  $form['hook'] = array(
    '#type' => 'hidden',
    '#value' => $hook,
  );
  $form['operation'] = array(
    '#type' => 'hidden',
    '#value' => $op,
  );
  // All of these forms use the same validate and submit functions.
  $form['#validate'][] = 'trigger_assign_form_validate';
  $form['#submit'][] = 'trigger_assign_form_submit';

  $options = array();
  $functions = array();
  // Restrict the options list to actions that declare support for this hook-op
  // combination.
  foreach (actions_list() as $func => $metadata) {
    if (isset($metadata['hooks']['any']) || (isset($metadata['hooks'][$hook]) && is_array($metadata['hooks'][$hook]) && (in_array($op, $metadata['hooks'][$hook])))) {
      $functions[] = $func;
    }
  }
  foreach (actions_actions_map(actions_get_all_actions()) as $aid => $action) {
    if (in_array($action['callback'], $functions)) {
      $options[$action['type']][$aid] = $action['description'];
    }
  }

  $form[$op] = array(
    '#type' => 'fieldset',
    '#title' => t('Trigger: ') . $description,
    '#theme' => 'trigger_display'
    );
  // Retrieve actions that are already assigned to this hook-op combination.
  $actions = _trigger_get_hook_actions($hook, $op);
  $form[$op]['assigned']['#type'] = 'value';
  $form[$op]['assigned']['#value'] = array();
  foreach ($actions as $aid => $description) {
    $form[$op]['assigned']['#value'][$aid] = array(
      'description' => $description,
      'link' => l(t('unassign'), "admin/build/trigger/unassign/$hook/$op/". md5($aid))
    );
  }

  $form[$op]['parent'] = array(
    '#prefix' => "<div class='container-inline'>",
    '#suffix' => '</div>',
  );
  // List possible actions that may be assigned.
  if (count($options) != 0) {
    array_unshift($options, t('Choose an action'));
    $form[$op]['parent']['aid'] = array(
      '#type' => 'select',
      '#options' => $options,
    );
    $form[$op]['parent']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Assign')
    );
  }
  else {
    $form[$op]['none'] = array(
      '#value' => t('No available actions for this trigger.')
    );
  }
  return $form;
}

/**
 * Validation function for trigger_assign_form().
 *
 * Makes sure that the user is not re-assigning an action to an event.
 */
function trigger_assign_form_validate($form, $form_state) {
  $form_values = $form_state['values'];
  if (!empty($form_values['aid'])) {
    $aid = actions_function_lookup($form_values['aid']);
    if (db_result(db_query("SELECT aid FROM {trigger_assignments} WHERE hook = '%s' AND op = '%s' AND aid = '%s'", $form_values['hook'], $form_values['operation'], $aid))) {
      form_set_error($form_values['operation'], t('The action you chose is already assigned to that trigger.'));
    }
  }
}

/**
 * Submit function for trigger_assign_form().
 */
function trigger_assign_form_submit($form, $form_state) {
  $form_values = $form_state['values'];

  if (!empty($form_values['aid'])) {
    $aid = actions_function_lookup($form_values['aid']);
    $weight = db_result(db_query("SELECT MAX(weight) FROM {trigger_assignments} WHERE hook = '%s' AND op = '%s'", $form_values['hook'], $form_values['operation']));
    db_query("INSERT INTO {trigger_assignments} values ('%s', '%s', '%s', %d)", $form_values['hook'], $form_values['operation'], $aid, $weight + 1);
    // If this action changes a node property, we need to save the node
    // so the change will persist.
    $actions = actions_list();
    if (isset($actions[$aid]['behavior']) && in_array('changes_node_property', $actions[$aid]['behavior']) && ($form_values['operation'] != 'presave')) {
      // Delete previous node_save_action if it exists, and re-add a new one at a higher weight.
      $save_post_action_assigned = db_result(db_query("SELECT aid FROM {trigger_assignments} WHERE hook = '%s' AND op = '%s' AND aid = 'node_save_action'", $form_values['hook'], $form_values['operation']));
      if ($save_post_action_assigned) {
        db_query("DELETE FROM {trigger_assignments} WHERE hook = '%s' AND op = '%s' AND aid = 'node_save_action'", $form_values['hook'], $form_values['operation']);
      }
      db_query("INSERT INTO {trigger_assignments} VALUES ('%s', '%s', '%s', %d)", $form_values['hook'], $form_values['operation'], 'node_save_action', $weight + 2);
      if (!$save_post_action_assigned) {
        drupal_set_message(t('You have added an action that changes a the property of a post. A Save post action has been added so that the property change will be saved.'));
      }
    }
  }
}

/**
 * Display actions assigned to this hook-op combination in a table.
 *
 * @param array $element
 *   The fieldset including all assigned actions.
 * @return
 *   The rendered form with the table prepended.
 *
 * @ingroup themeable
 */
function theme_trigger_display($element) {
  $header = array();
  $rows = array();
  if (count($element['assigned']['#value'])) {
    $header = array(array('data' => t('Name')), array('data' => t('Operation')));
    $rows = array();
    foreach ($element['assigned']['#value'] as $aid => $info) {
      $rows[] = array(
        $info['description'],
        $info['link']
      );
    }
  }

  if (count($rows)) {
    $output = theme('table', $header, $rows) . drupal_render($element);
  }
  else {
    $output = drupal_render($element);
  }
  return $output;
}


/**
 * Get the actions that have already been defined for this
 * type-hook-op combination.
 *
 * @param $type
 *   One of 'node', 'user', 'comment'.
 * @param $hook
 *   The name of the hook for which actions have been assigned,
 *   e.g. 'nodeapi'.
 * @param $op
 *   The hook operation for which the actions have been assigned,
 *   e.g., 'view'.
 * @return
 *   An array of action descriptions keyed by action IDs.
 */
function _trigger_get_hook_actions($hook, $op, $type = NULL) {
  $actions = array();
  if ($type) {
    $result = db_query("SELECT h.aid, a.description FROM {trigger_assignments} h LEFT JOIN {actions} a on a.aid = h.aid WHERE a.type = '%s' AND h.hook = '%s' AND h.op = '%s' ORDER BY h.weight", $type, $hook, $op);
  }
  else {
    $result = db_query("SELECT h.aid, a.description FROM {trigger_assignments} h LEFT JOIN {actions} a on a.aid = h.aid WHERE h.hook = '%s' AND h.op = '%s' ORDER BY h.weight", $hook, $op);
  }
  while ($action = db_fetch_object($result)) {
    $actions[$action->aid] = $action->description;
  }
  return $actions;
}
