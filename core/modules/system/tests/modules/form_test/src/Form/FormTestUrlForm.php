<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestUrlForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Form constructor for testing #type 'url' elements.
 */
class FormTestUrlForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_url';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['url'] = array(
      '#type' => 'url',
      '#title' => 'Optional URL',
      '#description' => 'An optional URL field.',
    );
    $form['url_required'] = array(
      '#type' => 'url',
      '#title' => 'Required URL',
      '#description' => 'A required URL field.',
      '#required' => TRUE,
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
  public function submitForm(array &$form, array &$form_state) {
    $form_state['response'] = new JsonResponse($form_state['values']);
  }

}
