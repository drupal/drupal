<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestLimitValidationErrorsForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;

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
  public function buildForm(array $form, array &$form_state) {
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => 'Title',
      '#required' => TRUE,
    );

    $form['test'] = array(
      '#title' => 'Test',
      '#type' => 'textfield',
      '#element_validate' => array(array($this, 'elementValidateLimitValidationErrors')),
    );
    $form['test_numeric_index'] = array(
      '#tree' => TRUE,
    );
    $form['test_numeric_index'][0] = array(
      '#title' => 'Test (numeric index)',
      '#type' => 'textfield',
      '#element_validate' => array(array($this, 'elementValidateLimitValidationErrors')),
    );

    $form['test_substring'] = array(
      '#tree' => TRUE,
    );
    $form['test_substring']['foo'] = array(
      '#title' => 'Test (substring) foo',
      '#type' => 'textfield',
      '#element_validate' => array(array($this, 'elementValidateLimitValidationErrors')),
    );
    $form['test_substring']['foobar'] = array(
      '#title' => 'Test (substring) foobar',
      '#type' => 'textfield',
      '#element_validate' => array(array($this, 'elementValidateLimitValidationErrors')),
    );

    $form['actions']['partial'] = array(
      '#type' => 'submit',
      '#limit_validation_errors' => array(array('test')),
      '#submit' => array(array($this, 'partialSubmitForm')),
      '#value' => t('Partial validate'),
    );
    $form['actions']['partial_numeric_index'] = array(
      '#type' => 'submit',
      '#limit_validation_errors' => array(array('test_numeric_index', 0)),
      '#submit' => array(array($this, 'partialSubmitForm')),
      '#value' => t('Partial validate (numeric index)'),
    );
    $form['actions']['substring'] = array(
      '#type' => 'submit',
      '#limit_validation_errors' => array(array('test_substring', 'foo')),
      '#submit' => array(array($this, 'partialSubmitForm')),
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
  public function elementValidateLimitValidationErrors($element, &$form_state) {
    if ($element['#value'] == 'invalid') {
      form_error($element, $form_state, t('@label element is invalid', array('@label' => $element['#title'])));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function partialSubmitForm(array &$form, array &$form_state) {
    // The title has not been validated, thus its value - in case of the test case
    // an empty string - may not be set.
    if (!isset($form_state['values']['title']) && isset($form_state['values']['test'])) {
      drupal_set_message('Only validated values appear in the form values.');
    }
  }

}
