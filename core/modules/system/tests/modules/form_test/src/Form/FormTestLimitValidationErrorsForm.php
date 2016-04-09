<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a simple form with a button triggering partial validation.
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
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => 'Title',
      '#required' => TRUE,
    );

    $form['test'] = array(
      '#title' => 'Test',
      '#type' => 'textfield',
      '#element_validate' => array('::elementValidateLimitValidationErrors'),
    );
    $form['test_numeric_index'] = array(
      '#tree' => TRUE,
    );
    $form['test_numeric_index'][0] = array(
      '#title' => 'Test (numeric index)',
      '#type' => 'textfield',
      '#element_validate' => array('::elementValidateLimitValidationErrors'),
    );

    $form['test_substring'] = array(
      '#tree' => TRUE,
    );
    $form['test_substring']['foo'] = array(
      '#title' => 'Test (substring) foo',
      '#type' => 'textfield',
      '#element_validate' => array('::elementValidateLimitValidationErrors'),
    );
    $form['test_substring']['foobar'] = array(
      '#title' => 'Test (substring) foobar',
      '#type' => 'textfield',
      '#element_validate' => array('::elementValidateLimitValidationErrors'),
    );

    $form['actions']['partial'] = array(
      '#type' => 'submit',
      '#limit_validation_errors' => array(array('test')),
      '#submit' => array('::partialSubmitForm'),
      '#value' => t('Partial validate'),
    );
    $form['actions']['partial_numeric_index'] = array(
      '#type' => 'submit',
      '#limit_validation_errors' => array(array('test_numeric_index', 0)),
      '#submit' => array('::partialSubmitForm'),
      '#value' => t('Partial validate (numeric index)'),
    );
    $form['actions']['substring'] = array(
      '#type' => 'submit',
      '#limit_validation_errors' => array(array('test_substring', 'foo')),
      '#submit' => array('::partialSubmitForm'),
      '#value' => t('Partial validate (substring)'),
    );
    $form['actions']['full'] = array(
      '#type' => 'submit',
      '#value' => t('Full validate'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function elementValidateLimitValidationErrors($element, FormStateInterface $form_state) {
    if ($element['#value'] == 'invalid') {
      $form_state->setError($element, t('@label element is invalid', array('@label' => $element['#title'])));
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
      drupal_set_message('Only validated values appear in the form values.');
    }
  }

}
