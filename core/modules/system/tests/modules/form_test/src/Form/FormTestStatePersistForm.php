<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form constructor for testing form state persistence.
 *
 * @internal
 */
class FormTestStatePersistForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_state_persist';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => 'title',
      '#default_value' => 'DEFAULT',
      '#required' => TRUE,
    ];
    $form_state->set('value', 'State persisted.');

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addStatus($form_state->get('value'));
    $form_state->setRebuild();
  }

}
