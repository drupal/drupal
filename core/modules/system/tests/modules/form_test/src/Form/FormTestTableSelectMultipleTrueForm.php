<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestTableSelectMultipleTrueForm.
 */

namespace Drupal\form_test\Form;

class FormTestTableSelectMultipleTrueForm extends FormTestTableSelectFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_form_test_tableselect_multiple_true_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    return $this->tableselectFormBuilder($form, $form_state, array('#multiple' => TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $selected = $form_state['values']['tableselect'];
    foreach ($selected as $key => $value) {
      drupal_set_message(t('Submitted: @key = @value', array('@key' => $key, '@value' => $value)));
    }
  }

}
