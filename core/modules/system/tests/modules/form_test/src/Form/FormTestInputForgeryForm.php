<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class FormTestInputForgeryForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_form_test_input_forgery';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // For testing that a user can't submit a value not matching one of the
    // allowed options.
    $form['checkboxes'] = [
      '#title' => t('Checkboxes'),
      '#type' => 'checkboxes',
      '#options' => [
        'one' => 'One',
        'two' => 'Two',
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return new JsonResponse($form_state->getValues());
  }

}
