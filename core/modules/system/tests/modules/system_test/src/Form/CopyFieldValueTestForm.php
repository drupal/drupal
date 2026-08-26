<?php

declare(strict_types=1);

namespace Drupal\system_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\Attribute\Route;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a form to test Drupal.behaviors.copyFieldValue.
 */
#[Route(
  path: '/system-test/copy-field-value-test-form',
  name: 'system_test.copy_field_value',
  title: new TranslatableMarkup('Copy Field Value Test Form'),
  requirements: ['_access' => 'TRUE'],
)]
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
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // We are only testing the JavaScript part of form. We are not submitting
    // form.
  }

}
