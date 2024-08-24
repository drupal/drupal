<?php

declare(strict_types=1);

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
    $options = match ($test_action) {
      'multiple-true-default' => ['#multiple' => TRUE],
      'multiple-false-default' => ['#multiple' => FALSE],
      'multiple-true-no-advanced-select' => ['#multiple' => TRUE, '#js_select' => FALSE],
      'multiple-false-advanced-select' => ['#multiple' => FALSE, '#js_select' => TRUE],
    };

    return $this->tableselectFormBuilder($form, $form_state, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
