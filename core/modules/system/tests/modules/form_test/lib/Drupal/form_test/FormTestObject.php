<?php

/**
 * @file
 * Contains \Drupal\form_test\FormTestObject.
 */

namespace Drupal\form_test;

use Drupal\Core\Form\FormInterface;

/**
 * Provides a test form object.
 */
class FormTestObject implements FormInterface {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'form_test_form_test_object';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::build().
   */
  public function build(array $form, array &$form_state) {
    $form['element'] = array('#markup' => 'The FormTestObject::build() method was used for this form.');

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validate().
   */
  public function validate(array &$form, array &$form_state) {
    drupal_set_message(t('The FormTestObject::validate() method was used for this form.'));
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submit().
   */
  public function submit(array &$form, array &$form_state) {
    drupal_set_message(t('The FormTestObject::submit() method was used for this form.'));
  }

}
