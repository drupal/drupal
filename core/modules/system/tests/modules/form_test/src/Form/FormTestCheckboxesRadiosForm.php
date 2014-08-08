<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestCheckboxesRadiosForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Form constructor to test expansion of #type checkboxes and radios.
 */
class FormTestCheckboxesRadiosForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_checkboxes_radios';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $customize = FALSE) {
    // Expand #type checkboxes, setting custom element properties for some but not
    // all options.
    $form['checkboxes'] = array(
      '#type' => 'checkboxes',
      '#title' => 'Checkboxes',
      '#options' => array(
        0 => 'Zero',
        'foo' => 'Foo',
        1 => 'One',
        'bar' => 'Bar',
        '>' => 'Special Char',
      ),
    );
    if ($customize) {
      $form['checkboxes'] += array(
        'foo' => array(
          '#description' => 'Enable to foo.',
        ),
        1 => array(
          '#weight' => 10,
        ),
      );
    }

    // Expand #type radios, setting custom element properties for some but not
    // all options.
    $form['radios'] = array(
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => array(
        0 => 'Zero',
        'foo' => 'Foo',
        1 => 'One',
        'bar' => 'Bar',
        '>' => 'Special Char',
      ),
    );
    if ($customize) {
      $form['radios'] += array(
        'foo' => array(
          '#description' => 'Enable to foo.',
        ),
        1 => array(
          '#weight' => 10,
        ),
      );
    }

    $form['submit'] = array('#type' => 'submit', '#value' => 'Submit');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }

}
