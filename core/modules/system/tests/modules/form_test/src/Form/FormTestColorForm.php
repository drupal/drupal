<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Form constructor for testing #type 'color' elements.
 */
class FormTestColorForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_color';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['color'] = [
      '#type' => 'color',
      '#title' => 'Color',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }

}
