<?php

namespace Drupal\drupal_autocomplete_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form that uses shimmed and non-shimmed autocomplete inputs.
 */
class AutocompleteSelectiveShimForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'drupal_autocomplete_selective_shim_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['shimmed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shimmed'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
    ];

    $form['not_shimmed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Not Shimmed'),
      '#default_value' => '',
      '#autocomplete_route_name' => 'drupal_autocomplete.country_autocomplete',
      '#use-core-autocomplete' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Intentionally empty.
  }

}
