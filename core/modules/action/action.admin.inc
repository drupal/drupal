<?php

/**
 * @file
 * Admin page callbacks for the Action module.
 */

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
    'admin/config/system/actions',
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
  $form_state['redirect'] = 'admin/config/system/actions';
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

