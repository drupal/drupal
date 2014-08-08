<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestRangeForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Form constructor for testing #type 'range' elements.
 */
class FormTestRangeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_range';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['with_default_value'] = array(
      '#type' => 'range',
      '#title' => 'Range with default value',
      '#min' => 10,
      '#max' => 20,
      '#step' => 2,
      '#default_value' => 18,
      '#description' => 'The default value is 18.',
    );
    $form['float'] = array(
      '#type' => 'range',
      '#title' => 'Float',
      '#min' => 10,
      '#max' => 11,
      '#step' => 'any',
      '#description' => 'Floating point number between 10 and 11.',
    );
    $form['integer'] = array(
      '#type' => 'range',
      '#title' => 'Integer',
      '#min' => 2,
      '#max' => 8,
      '#step' => 2,
      '#description' => 'Even integer between 2 and 8.',
    );
    $form['offset'] = array(
      '#type' => 'range',
      '#title' => 'Offset',
      '#min' => 2.9,
      '#max' => 10.9,
      '#description' => 'Value between 2.9 and 10.9.',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Submit',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }

}
