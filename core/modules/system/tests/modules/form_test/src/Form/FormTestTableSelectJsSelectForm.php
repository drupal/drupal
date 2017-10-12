<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form to test table select with JS.
 *
 * @internal
 */
class FormTestTableSelectJsSelectForm extends FormTestTableSelectFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_form_test_tableselect_js_select_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $test_action = NULL) {
    switch ($test_action) {
      case 'multiple-true-default':
        $options = ['#multiple' => TRUE];
        break;

      case 'multiple-false-default':
        $options = ['#multiple' => FALSE];
        break;

      case 'multiple-true-no-advanced-select':
        $options = ['#multiple' => TRUE, '#js_select' => FALSE];
        break;

      case 'multiple-false-advanced-select':
        $options = ['#multiple' => FALSE, '#js_select' => TRUE];
        break;
    }

    return $this->tableselectFormBuilder($form, $form_state, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
