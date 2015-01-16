<?php

/**
 * @file
 * Contains \Drupal\form_test\FormTestObject.
 */

namespace Drupal\form_test;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a test form object.
 */
class FormTestObject extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_form_test_object';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['form_test.object'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['element'] = array('#markup' => 'The FormTestObject::buildForm() method was used for this form.');

    $form['bananas'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Bananas'),
    );

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    $form['#title'] = 'Test dynamic title';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The FormTestObject::validateForm() method was used for this form.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('The FormTestObject::submitForm() method was used for this form.'));
    $this->config('form_test.object')
      ->set('bananas', $form_state->getValue('bananas'))
      ->save();
  }

}
