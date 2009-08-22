<?php
// $Id$

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
    drupal_goto('admin/structure/trigger/node');
  }

  $build = array();
  $hooks = module_invoke_all('hook_info');
  foreach ($hooks as $module => $module_hooks) {
    if ($module == $type) {
      foreach ($module_hooks as $hook => $data) {
        foreach ($data as $op => $description) {
          $form_id = 'trigger_' . $hook . '_' . $op . '_assign_form';
          $build[$form_id] = drupal_get_form($form_id, $hook, $op, $description['runs when']);
        }
      }
    }
  }
  return $build;
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
    drupal_goto('admin/structure/trigger/assign');
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

  $destination = 'admin/structure/trigger/' . ($hook == 'node' ? 'node' : $hook);

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
    db_delete('trigger_assignments')
      ->condition('hook', $form_values['hook'])
      ->condition('op', $form_values['operation'])
      ->condition('aid', $aid)
      ->execute();
    $actions = actions_get_all_actions();
    watchdog('actions', 'Action %action has been unassigned.',  array('%action' => check_plain($actions[$aid]['description'])));
    drupal_set_message(t('Action %action has been unassigned.', array('%action' => $actions[$aid]['description'])));
    $hook = $form_values['hook'] == 'node' ? 'node' : $form_values['hook'];
    $form_state['redirect'] = 'admin/structure/trigger/' . $hook;
  }
  else {
    drupal_goto('admin/structure/trigger');
  }
}

/**
 * Create the form definition for assigning an action to a hook-op combination.
 *
 * @param $form_state
 *   Information about the current form.
 * @param $hook
 *   The name of the hook, e.g., 'node'.
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
      'link' => l(t('unassign'), "admin/structure/trigger/unassign/$hook/$op/" . md5($aid))
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
      '#markup' => t('No actions available for this trigger. <a href="@link">Add action</a>.', array('@link' => url('admin/settings/actions/manage')))
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
    $aid_exists = db_query("SELECT aid FROM {trigger_assignments} WHERE hook = :hook AND op = :op AND aid = :aid", array(
      ':hook' => $form_values['hook'],
      ':op' => $form_values['operation'],
      ':aid' => $aid,
    ))->fetchField();
    if ($aid_exists) {
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
    $weight = db_query("SELECT MAX(weight) FROM {trigger_assignments} WHERE hook = :hook AND op = :op", array(
      ':hook' => $form_values['hook'],
      ':op' => $form_values['operation'],
    ))->fetchField();

    db_insert('trigger_assignments')
      ->fields(array(
        'hook' => $form_values['hook'], 
        'op' => $form_values['operation'], 
        'aid' => $aid, 
        'weight' => $weight + 1,
      ))
      ->execute();
    // If this action changes a node property, we need to save the node
    // so the change will persist.
    $actions = actions_list();
    if (isset($actions[$aid]['behavior']) && in_array('changes_node_property', $actions[$aid]['behavior']) && ($form_values['operation'] != 'presave')) {
      // Delete previous node_save_action if it exists, and re-add a new one at a higher weight.
      $save_post_action_assigned = db_query("SELECT aid FROM {trigger_assignments} WHERE hook = :hook AND op = :op AND aid = :aid", array(
        ':hook' => $form_values['hook'],
        ':op' => $form_values['operation'],
        ':aid' => 'node_save_action',
      ))->fetchField();

      if ($save_post_action_assigned) {
        db_delete('trigger_assignments')
          ->condition('hook', $form_values['hook'])
          ->condition('op', $form_values['operation'])
          ->condition('aid', 'node_save_action')
          ->execute();
      }
      db_insert('trigger_assignments')
        ->fields(array(
          'hook' => $form_values['hook'], 
          'op' => $form_values['operation'], 
          'aid' => 'node_save_action', 
          'weight' => $weight + 2,
        ))
        ->execute();
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
    $output = theme('table', $header, $rows) . drupal_render_children($element);
  }
  else {
    $output = drupal_render_children($element);
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
 *   e.g. 'node'.
 * @param $op
 *   The hook operation for which the actions have been assigned,
 *   e.g., 'view'.
 * @return
 *   An array of action descriptions keyed by action IDs.
 */
function _trigger_get_hook_actions($hook, $op, $type = NULL) {
  if ($type) {
    return db_query("SELECT h.aid, a.description FROM {trigger_assignments} h LEFT JOIN {actions} a on a.aid = h.aid WHERE a.type = :type AND h.hook = :hook AND h.op = :op ORDER BY h.weight", array(
      ':type' => $type,
      ':hook' => $hook,
      ':op' => $op,
    ))->fetchAllKeyed();
  }
  else {
    return db_query("SELECT h.aid, a.description FROM {trigger_assignments} h LEFT JOIN {actions} a on a.aid = h.aid WHERE h.hook = :hook AND h.op = :op ORDER BY h.weight", array(
      ':hook' => $hook,
      ':op' => $op,
    ))->fetchAllKeyed();
  }
}
