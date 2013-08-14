<?php

/**
 * @file
 * Contains \Drupal\form_test\FormTestArgumentsObject.
 */

namespace Drupal\form_test;

use Drupal\Core\Form\FormInterface;

/**
 * Provides a test form object that needs arguments.
 */
class FormTestArgumentsObject implements FormInterface {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'form_test_form_test_arguments_object';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state, $arg = NULL) {
    $form['element'] = array('#markup' => 'The FormTestArgumentsObject::buildForm() method was used for this form.');

    $form['bananas'] = array(
      '#type' => 'textfield',
      '#default_value' => check_plain($arg),
      '#title' => t('Bananas'),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    drupal_set_message(t('The FormTestArgumentsObject::validateForm() method was used for this form.'));
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_set_message(t('The FormTestArgumentsObject::submitForm() method was used for this form.'));
    \Drupal::config('form_test.object')
      ->set('bananas', $form_state['values']['bananas'])
      ->save();
  }

}
