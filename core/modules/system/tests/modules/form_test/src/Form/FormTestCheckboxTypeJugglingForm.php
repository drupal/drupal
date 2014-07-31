<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestCheckboxTypeJugglingForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form to test return values for checkboxes.
 */
class FormTestCheckboxTypeJugglingForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_checkbox_type_juggling';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $default_value = NULL, $return_value = NULL) {
    $form['checkbox'] = array(
      '#title' => t('Checkbox'),
      '#type' => 'checkbox',
      '#return_value' => $return_value,
      '#default_value' => $default_value,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
