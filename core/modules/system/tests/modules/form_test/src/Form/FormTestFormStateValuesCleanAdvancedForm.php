<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form builder for \Drupal\Core\Form\FormState::cleanValues() test.
 *
 * @internal
 */
class FormTestFormStateValuesCleanAdvancedForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_form_state_clean_values_advanced_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Build an example form containing a managed file and a submit form
    // element.
    $form['image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#upload_location' => 'public://',
      '#default_value' => 0,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    print $this->t('You WIN!');
    exit;
  }

}
