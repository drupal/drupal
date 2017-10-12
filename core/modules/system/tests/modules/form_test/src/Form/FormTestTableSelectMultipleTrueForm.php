<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form to test table select with '#multiple' as TRUE.
 *
 * @internal
 */
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    return $this->tableselectFormBuilder($form, $form_state, ['#multiple' => TRUE]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected = $form_state->getValue('tableselect');
    foreach ($selected as $key => $value) {
      drupal_set_message(t('Submitted: @key = @value', ['@key' => $key, '@value' => $value]));
    }
  }

}
