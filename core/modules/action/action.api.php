<?php

/**
 * @file
 * Hooks provided by the Actions module.
 */

/**
 * Declares information about actions.
 *
 * Any module can define actions, and then call actions_do() to make those
 * actions happen in response to events.
 *
 * An action consists of two or three parts:
 * - an action definition (returned by this hook)
 * - a function which performs the action (which by convention is named
 *   MODULE_description-of-function_action)
 * - an optional form definition function that defines a configuration form
 *   (which has the name of the action function with '_form' appended to it.)
 *
 * The action function takes two to four arguments, which come from the input
 * arguments to actions_do().
 *
 * @return
 *   An associative array of action descriptions. The keys of the array
 *   are the names of the action functions, and each corresponding value
 *   is an associative array with the following key-value pairs:
 *   - 'type': The type of object this action acts upon. Core actions have types
 *     'node', 'user', 'comment', and 'system'.
 *   - 'label': The human-readable name of the action, which should be passed
 *     through the t() function for translation.
 *   - 'configurable': If FALSE, then the action doesn't require any extra
 *     configuration. If TRUE, then your module must define a form function with
 *     the same name as the action function with '_form' appended (e.g., the
 *     form for 'node_assign_owner_action' is 'node_assign_owner_action_form'.)
 *     This function takes $context as its only parameter, and is paired with
 *     the usual _submit function, and possibly a _validate function.
 *   - 'triggers': An array of the events (that is, hooks) that can trigger this
 *     action. For example: array('node_insert', 'user_update'). You can also
 *     declare support for any trigger by returning array('any') for this value.
 *   - 'behavior': (optional) A machine-readable array of behaviors of this
 *     action, used to signal additionally required actions that may need to be
 *     triggered. Modules that are processing actions should take special care
 *     for the "presave" hook, in which case a dependent "save" action should
 *     NOT be invoked.
 *
 * @ingroup actions
 */
function hook_action_info() {
  return array(
    'comment_unpublish_action' => array(
      'type' => 'comment',
      'label' => t('Unpublish comment'),
      'configurable' => FALSE,
      'behavior' => array('changes_property'),
      'triggers' => array('comment_presave', 'comment_insert', 'comment_update'),
    ),
    'comment_unpublish_by_keyword_action' => array(
      'type' => 'comment',
      'label' => t('Unpublish comment containing keyword(s)'),
      'configurable' => TRUE,
      'behavior' => array('changes_property'),
      'triggers' => array('comment_presave', 'comment_insert', 'comment_update'),
    ),
    'comment_save_action' => array(
      'type' => 'comment',
      'label' => t('Save comment'),
      'configurable' => FALSE,
      'triggers' => array('comment_insert', 'comment_update'),
    ),
  );
}

/**
 * Alters the actions declared by another module.
 *
 * Called by action_list() to allow modules to alter the return values from
 * implementations of hook_action_info().
 *
 * @ingroup actions
 */
function hook_action_info_alter(&$actions) {
  $actions['node_unpublish_action']['label'] = t('Unpublish and remove from public view.');
}

/**
 * Executes code after an action is deleted.
 *
 * @param $aid
 *   The action ID.
 *
 * @ingroup actions
 */
function hook_action_delete($aid) {
  db_delete('actions_assignments')
    ->condition('aid', $aid)
    ->execute();
}
