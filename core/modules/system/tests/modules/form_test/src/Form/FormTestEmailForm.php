<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestEmailForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Form constructor for testing #type 'email' elements.
 */
class FormTestEmailForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_email';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = array(
      '#type' => 'email',
      '#title' => 'Email address',
      '#description' => 'An email address.',
    );
    $form['email_required'] = array(
      '#type' => 'email',
      '#title' => 'Address',
      '#required' => TRUE,
      '#description' => 'A required email address field.',
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
