<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestTableSelectMultipleFalseForm.
 */

namespace Drupal\form_test\Form;

class FormTestTableSelectMultipleFalseForm extends FormTestTableSelectFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_form_test_tableselect_multiple_false_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    return $this->tableselectFormBuilder($form, $form_state, array('#multiple' => FALSE));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    drupal_set_message(t('Submitted: @value', array('@value' => $form_state['values']['tableselect'])));
  }

}
