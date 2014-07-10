<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestTableSelectEmptyForm.
 */

namespace Drupal\form_test\Form;

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
  public function buildForm(array $form, array &$form_state) {
    return $this->tableselectFormBuilder($form, $form_state, array('#options' => array()));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }

}
