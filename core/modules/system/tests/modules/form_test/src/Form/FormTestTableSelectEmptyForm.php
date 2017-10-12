<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form to test table select with '#options' set to empty.
 *
 * @internal
 */
class FormTestTableSelectEmptyForm extends FormTestTableSelectFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_form_test_tableselect_empty_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    return $this->tableselectFormBuilder($form, $form_state, ['#options' => []]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
