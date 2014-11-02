<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestResponseForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Form constructor for testing #type 'url' elements.
 */
class FormTestResponseForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_response';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['content'] = array(
      '#type' => 'textfield',
      '#title' => 'Content',
    );
    $form['status'] = array(
      '#type' => 'textfield',
      '#title' => 'Status',
      '#default_value' => 200,
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
    $values = $form_state->getValues();
    $form_state->setResponse(new JsonResponse($values['content'], (int) $values['status']));
  }

}
