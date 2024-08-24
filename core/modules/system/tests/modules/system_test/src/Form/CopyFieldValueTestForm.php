<?php

declare(strict_types=1);

namespace Drupal\system_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form to test Drupal.behaviors.copyFieldValue.
 */
class CopyFieldValueTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'copy_field_value_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'system/drupal.system';
    $form['#attached']['drupalSettings']['copyFieldValue']['edit-source-field'] = ['edit-target-field'];

    $form['source_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source Field'),
      '#default_value' => '',
      '#description' => $this->t('Source input field to provide text value.'),
      '#required' => TRUE,
    ];
    $form['target_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Target Field'),
      '#default_value' => '',
      '#description' => $this->t('Target input field to get value from source field.'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // We are only testing the JavaScript part of form. We are not submitting
    // form.
  }

}
