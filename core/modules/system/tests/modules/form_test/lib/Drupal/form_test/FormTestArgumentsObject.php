<?php

/**
 * @file
 * Contains \Drupal\form_test\FormTestArgumentsObject.
 */

namespace Drupal\form_test;

use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormBase;

/**
 * Provides a test form object that needs arguments.
 */
class FormTestArgumentsObject extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_form_test_arguments_object';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $arg = NULL) {
    $form['element'] = array('#markup' => 'The FormTestArgumentsObject::buildForm() method was used for this form.');

    $form['bananas'] = array(
      '#type' => 'textfield',
      '#default_value' => String::checkPlain($arg),
      '#title' => $this->t('Bananas'),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    drupal_set_message($this->t('The FormTestArgumentsObject::validateForm() method was used for this form.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_set_message($this->t('The FormTestArgumentsObject::submitForm() method was used for this form.'));
    $this->config('form_test.object')
      ->set('bananas', $form_state['values']['bananas'])
      ->save();
  }

}
