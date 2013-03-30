<?php

/**
 * @file
 * Contains \Drupal\action\Form\ActionAdminManageForm.
 */

namespace Drupal\action\Form;

use Drupal\Core\Form\FormInterface;

/**
 * Provides a configuration form for configurable actions.
 */
class ActionAdminManageForm implements FormInterface {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'action_admin_manage';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   *
   * @param array $options
   *   An array of configurable actions.
   */
  public function buildForm(array $form, array &$form_state, array $options = array()) {
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
    $form['parent']['actions'] = array(
      '#type' => 'actions'
    );
    $form['parent']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Create'),
    );
    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    if ($form_state['values']['action']) {
      $form_state['redirect'] = 'admin/config/system/actions/configure/' . $form_state['values']['action'];
    }
  }

}
