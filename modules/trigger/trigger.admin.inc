<?php
// $Id: trigger.admin.inc,v 1.30 2010/10/20 16:19:24 webchick Exp $

/**
 * @file
 * Admin page callbacks for the trigger module.
 */

/**
 * Builds the form that allows users to assign actions to triggers.
 *
 * @param $module_to_display
 *   Which tab of triggers to display. E.g., 'node' for all
 *   node-related triggers.
 * @return
 *   HTML form.
 */
function trigger_assign($module_to_display = NULL) {
  // If no type is specified we default to node actions, since they
  // are the most common.
  if (!isset($module_to_display)) {
    drupal_goto('admin/structure/trigger/node');
  }

  $build = array();
  $trigger_info = module_invoke_all('trigger_info');
  drupal_alter('trigger_info', $trigger_info);
  foreach ($trigger_info as $module => $hooks) {
    if ($module == $module_to_display) {
      foreach ($hooks as $hook => $description) {
        $form_id = 'trigger_' . $hook . '_assign_form';
        $build[$form_id] = drupal_get_form($form_id, $module, $hook, $description['label']);
      }
    }
  }
  return $build;
}

/**
 * Confirm removal of an assigned action.
 *
 * @param $module
 *   The tab of triggers the user will be directed to after successful
 *   removal of the action, or if the confirmation form is cancelled.
 * @param $hook
 * @param $aid
 *   The action ID.
 * @ingroup forms
 * @see trigger_unassign_submit()
 */
function trigger_unassign($form, $form_state, $module, $hook = NULL, $aid = NULL) {
  if (!($hook && $aid)) {
    drupal_goto('admin/structure/trigger');
  }

  $form['hook'] = array(
    '#type' => 'value',
    '#value' => $hook,
  );
  $form['module'] = array(
    '#type' => 'value',
    '#value' => $module,
  );
  $form['aid'] = array(
    '#type' => 'value',
    '#value' => $aid,
  );

  $action = actions_function_lookup($aid);
  $actions = actions_get_all_actions();

  $destination = 'admin/structure/trigger/' . $module;

  return confirm_form($form,
    t('Are you sure you want to unassign the action %title?', array('%title' => $actions[$action]['label'])),
    $destination,
    t('You can assign it again later if you wish.'),
    t('Unassign'), t('Cancel')
  );
}

/**
 * Submit callback for trigger_unassign() form.
 */
function trigger_unassign_submit($form, &$form_state) {
  if ($form_state['values']['confirm'] == 1) {
    $aid = actions_function_lookup($form_state['values']['aid']);
    db_delete('trigger_assignments')
      ->condition('hook', $form_state['values']['hook'])
      ->condition('aid', $aid)
      ->execute();
    $actions = actions_get_all_actions();
    watchdog('actions', 'Action %action has been unassigned.',  array('%action' => $actions[$aid]['label']));
    drupal_set_message(t('Action %action has been unassigned.', array('%action' => $actions[$aid]['label'])));
    $form_state['redirect'] = 'admin/structure/trigger/' . $form_state['values']['module'];
  }
  else {
    drupal_goto('admin/structure/trigger');
  }
}

/**
 * Returns the form for assigning an action to a trigger.
 *
 * @param $module
 *   The name of the trigger group, e.g., 'node'.
 * @param $hook
 *   The name of the trigger hook, e.g., 'node_insert'.
 * @param $label
 *   A plain English description of what this trigger does.
 *
 * @ingoup forms
 * @see trigger_assign_form_validate()
 * @see trigger_assign_form_submit()
 */
function trigger_assign_form($form, $form_state, $module, $hook, $label) {
  $form['module'] = array(
    '#type' => 'hidden',
    '#value' => $module,
  );
  $form['hook'] = array(
    '#type' => 'hidden',
    '#value' => $hook,
  );
  // All of these forms use the same validate and submit functions.
  $form['#validate'][] = 'trigger_assign_form_validate';
  $form['#submit'][] = 'trigger_assign_form_submit';

  $options = array();
  $functions = array();
  // Restrict the options list to actions that declare support for this hook.
  foreach (actions_list() as $func => $metadata) {
    if (isset($metadata['triggers']) && array_intersect(array($hook, 'any'), $metadata['triggers'])) {
      $functions[] = $func;
    }
  }
  foreach (actions_actions_map(actions_get_all_actions()) as $aid => $action) {
    if (in_array($action['callback'], $functions)) {
      $options[$action['type']][$aid] = $action['label'];
    }
  }

  $form[$hook] = array(
    '#type' => 'fieldset',
    // !description is correct, since these labels are passed through t() in
    // hook_trigger_info().
    '#title' => t('Trigger: !description', array('!description' => $label)),
    '#theme' => 'trigger_display',
  );

  // Retrieve actions that are already assigned to this hook combination.
  $actions = trigger_get_assigned_actions($hook);
  $form[$hook]['assigned']['#type'] = 'value';
  $form[$hook]['assigned']['#value'] = array();
  foreach ($actions as $aid => $info) {
    // If action is defined unassign it, otherwise offer to delete all orphaned
    // actions.
    $hash = drupal_hash_base64($aid, TRUE);
    if (actions_function_lookup($hash)) {
      $form[$hook]['assigned']['#value'][$aid] = array(
        'label' => $info['label'],
        'link' => l(t('unassign'), "admin/structure/trigger/unassign/$module/$hook/$hash"),
      );
    }
    else {
      // Link to system_actions_remove_orphans() to do the clean up.
      $form[$hook]['assigned']['#value'][$aid] = array(
        'label' => $info['label'],
        'link' => l(t('Remove orphaned actions'), "admin/config/system/actions/orphan"),
      );
    }
  }

  $form[$hook]['parent'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('container-inline')),
  );
  // List possible actions that may be assigned.
  if (count($options) != 0) {
    $form[$hook]['parent']['aid'] = array(
      '#type' => 'select',
      '#title' => t('List of trigger actions when !description', array('!description' => $label)),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#empty_option' => t('Choose an action'),
    );
    $form[$hook]['parent']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Assign')
    );
  }
  else {
    $form[$hook]['none'] = array(
      '#markup' => t('No actions available for this trigger. <a href="@link">Add action</a>.', array('@link' => url('admin/config/system/actions/manage')))
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
    $aid_exists = db_query("SELECT aid FROM {trigger_assignments} WHERE hook = :hook AND aid = :aid", array(
      ':hook' => $form_values['hook'],
      ':aid' => $aid,
    ))->fetchField();
    if ($aid_exists) {
      form_set_error($form_values['hook'], t('The action you chose is already assigned to that trigger.'));
    }
  }
}

/**
 * Submit function for trigger_assign_form().
 */
function trigger_assign_form_submit($form, &$form_state) {
  if (!empty($form_state['values']['aid'])) {
    $aid = actions_function_lookup($form_state['values']['aid']);
    $weight = db_query("SELECT MAX(weight) FROM {trigger_assignments} WHERE hook = :hook", array(':hook' => $form_state['values']['hook']))->fetchField();

    // Insert the new action.
    db_insert('trigger_assignments')
      ->fields(array(
        'hook' => $form_state['values']['hook'],
        'aid' => $aid,
        'weight' => $weight + 1,
      ))
      ->execute();

    // If we are not configuring an action for a "presave" hook and this action
    // changes an object property, then we need to save the object, so the
    // property change will persist.
    $actions = actions_list();
    if (strpos($form_state['values']['hook'], 'presave') === FALSE && isset($actions[$aid]['behavior']) && in_array('changes_property', $actions[$aid]['behavior'])) {
      // Determine the corresponding save action name for this action.
      $save_action = strtok($aid, '_') . '_save_action';
      // If no corresponding save action exists, we need to bail out.
      if (!isset($actions[$save_action])) {
        throw new Exception(t('Missing/undefined save action (%save_aid) for %aid action.', array('%save_aid' => $aid, '%aid' => $aid)));
      }
      // Delete previous save action if it exists, and re-add it using a higher
      // weight.
      $save_action_assigned = db_query("SELECT aid FROM {trigger_assignments} WHERE hook = :hook AND aid = :aid", array(':hook' => $form_state['values']['hook'], ':aid' => $save_action))->fetchField();

      if ($save_action_assigned) {
        db_delete('trigger_assignments')
          ->condition('hook', $form_state['values']['hook'])
          ->condition('aid', $save_action)
          ->execute();
      }
      db_insert('trigger_assignments')
        ->fields(array(
          'hook' => $form_state['values']['hook'],
          'aid' => $save_action,
          'weight' => $weight + 2,
        ))
        ->execute();

      // If no save action existed before, inform the user about it.
      if (!$save_action_assigned) {
        drupal_set_message(t('The %label action has been appended, which is required to save the property change.', array('%label' => $actions[$save_action]['label'])));
      }
      // Otherwise, just inform about the new weight.
      else {
        drupal_set_message(t('The %label action was moved to save the property change.', array('%label' => $actions[$save_action]['label'])));
      }
    }
  }
}

/**
 * Returns HTML for the form showing actions assigned to a trigger.
 *
 * @param $variables
 *   An associative array containing:
 *   - element: The fieldset including all assigned actions.
 *
 * @ingroup themeable
 */
function theme_trigger_display($variables) {
  $element = $variables['element'];

  $header = array();
  $rows = array();
  if (isset($element['assigned']) && count($element['assigned']['#value'])) {
    $header = array(array('data' => t('Name')), array('data' => t('Operation')));
    $rows = array();
    foreach ($element['assigned']['#value'] as $aid => $info) {
      $rows[] = array(
        check_plain($info['label']),
        $info['link']
      );
    }
  }

  if (count($rows)) {
    $output = theme('table', array('header' => $header, 'rows' => $rows)) . drupal_render_children($element);
  }
  else {
    $output = drupal_render_children($element);
  }
  return $output;
}

