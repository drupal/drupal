<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form to test the #group property on #type 'details'.
 */
class FormTestGroupDetailsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_group_details';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $required = FALSE) {
    $form['details'] = [
      '#type' => 'details',
      '#title' => 'Root element',
      '#open' => TRUE,
      '#required' => !empty($required),
    ];
    $form['meta'] = [
      '#type' => 'details',
      '#title' => 'Group element',
      '#open' => TRUE,
      '#group' => 'details',
    ];
    $form['meta']['element'] = [
      '#type' => 'textfield',
      '#title' => 'Nest in details element',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
