<?php
/**
 * @file
 * Contains \Drupal\action\Form\ActionAdminConfigureForm.
 */

namespace Drupal\action\Form;

use Drupal\Core\Form\FormInterface;

/**
 * Provides a form for configuring an action.
 */
class ActionAdminConfigureForm implements FormInterface {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'action_admin_configure';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, $action = NULL) {
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
      '#weight' => -10,
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
      '#weight' => 13,
    );

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    $function = action_function_lookup($form_state['values']['action_action']) . '_validate';
    // Hand off validation to the action.
    if (function_exists($function)) {
      $function($form, $form_state);
    }
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $function = action_function_lookup($form_state['values']['action_action']);
    $submit_function = $function . '_submit';

    // Action will return keyed array of values to store.
    $params = $submit_function($form, $form_state);
    $aid = isset($form_state['values']['action_aid']) ? $form_state['values']['action_aid'] : NULL;

    action_save($function, $form_state['values']['action_type'], $params, $form_state['values']['action_label'], $aid);
    drupal_set_message(t('The action has been successfully saved.'));

    $form_state['redirect'] = 'admin/config/system/actions';
  }

}
