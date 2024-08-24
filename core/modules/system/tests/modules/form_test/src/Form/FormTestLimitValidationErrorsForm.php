<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form with a button triggering partial validation.
 *
 * @internal
 */
class FormTestLimitValidationErrorsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_limit_validation_errors_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => 'Title',
      '#required' => TRUE,
    ];

    $form['test'] = [
      '#title' => 'Test',
      '#type' => 'textfield',
      '#element_validate' => ['::elementValidateLimitValidationErrors'],
    ];
    $form['test_numeric_index'] = [
      '#tree' => TRUE,
    ];
    $form['test_numeric_index'][0] = [
      '#title' => 'Test (numeric index)',
      '#type' => 'textfield',
      '#element_validate' => ['::elementValidateLimitValidationErrors'],
    ];

    $form['test_substring'] = [
      '#tree' => TRUE,
    ];
    $form['test_substring']['foo'] = [
      '#title' => 'Test (substring) foo',
      '#type' => 'textfield',
      '#element_validate' => ['::elementValidateLimitValidationErrors'],
    ];
    $form['test_substring']['foobar'] = [
      '#title' => 'Test (substring) foobar',
      '#type' => 'textfield',
      '#element_validate' => ['::elementValidateLimitValidationErrors'],
    ];

    $form['actions']['partial'] = [
      '#type' => 'submit',
      '#limit_validation_errors' => [['test']],
      '#submit' => ['::partialSubmitForm'],
      '#value' => t('Partial validate'),
    ];
    $form['actions']['partial_numeric_index'] = [
      '#type' => 'submit',
      '#limit_validation_errors' => [['test_numeric_index', 0]],
      '#submit' => ['::partialSubmitForm'],
      '#value' => t('Partial validate (numeric index)'),
    ];
    $form['actions']['substring'] = [
      '#type' => 'submit',
      '#limit_validation_errors' => [['test_substring', 'foo']],
      '#submit' => ['::partialSubmitForm'],
      '#value' => t('Partial validate (substring)'),
    ];
    $form['actions']['full'] = [
      '#type' => 'submit',
      '#value' => t('Full validate'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function elementValidateLimitValidationErrors($element, FormStateInterface $form_state) {
    if ($element['#value'] == 'invalid') {
      $form_state->setError($element, t('@label element is invalid', ['@label' => $element['#title']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function partialSubmitForm(array &$form, FormStateInterface $form_state) {
    // The title has not been validated, thus its value - in case of the test case
    // an empty string - may not be set.
    if (!$form_state->hasValue('title') && $form_state->hasValue('test')) {
      $this->messenger()->addStatus('Only validated values appear in the form values.');
    }
  }

}
