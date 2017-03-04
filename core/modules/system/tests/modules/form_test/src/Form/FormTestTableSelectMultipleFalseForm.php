<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormStateInterface;

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
  public function buildForm(array $form, FormStateInterface $form_state) {
    return $this->tableselectFormBuilder($form, $form_state, ['#multiple' => FALSE]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message(t('Submitted: @value', ['@value' => $form_state->getValue('tableselect')]));
  }

}
