<?php

declare(strict_types=1);

namespace Drupal\form_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Form constructor to test #default_value settings of radios.
 *
 * @internal
 */
class FormTestRadiosCheckedForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_test_radios_checked';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['radios'] = [
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => [
        0 => 'Zero',
        'foo' => 'Foo',
        1 => 'One',
        'bar' => '<em>Bar - radios</em>',
        '>' => "<em>Special Char</em><script>alert('radios');</script>",
      ],
      '#default_value' => 0,
    ];
    $form['radios-string'] = [
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => [
        0 => 'Zero',
        'foo' => 'Foo',
        1 => 'One',
        'bar' => '<em>Bar - radios</em>',
        '>' => "<em>Special Char</em><script>alert('radios');</script>",
      ],
      '#default_value' => 'bar',
    ];
    $form['radios-boolean-true'] = [
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => [
        'All' => '- Any -',
        1 => 'True',
        0 => 'False',
      ],
      '#default_value' => TRUE,
    ];
    $form['radios-boolean-false'] = [
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => [
        'All' => '- Any -',
        1 => 'True',
        0 => 'False',
      ],
      '#default_value' => FALSE,
    ];
    $form['radios-boolean-any'] = [
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => [
        'All' => '- Any -',
        1 => 'True',
        0 => 'False',
      ],
      '#default_value' => 'All',
    ];
    $form['radios-string-zero'] = [
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => [
        'All' => '- Any -',
        '0' => 'Zero',
        100 => 'One hundred',
      ],
      '#default_value' => 0,
    ];
    $form['radios-int-non-zero'] = [
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => [
        'All' => '- Any -',
        0 => 'Zero',
        10 => 'Ten',
        100 => 'One hundred',
      ],
      '#default_value' => 10,
    ];
    $form['radios-int-non-zero-as-string'] = [
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => [
        'All' => '- Any -',
        '0' => 'Zero',
        '10' => 'Ten',
        '100' => 'One hundred',
      ],
      '#default_value' => '100',
    ];
    $form['radios-empty-string'] = [
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => [
        'All' => '- Any -',
        0 => 'None',
      ],
      '#default_value' => '',
    ];
    $form['radios-empty-array'] = [
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => [
        'All' => '- Any -',
        0 => 'None',
      ],
      '#default_value' => [],
    ];
    $form['radios-key-FALSE'] = [
      '#type' => 'radios',
      '#title' => 'Radios',
      '#options' => [
        'All' => '- Any -',
        FALSE => 'False',
      ],
      '#default_value' => '',
    ];

    $form['submit'] = ['#type' => 'submit', '#value' => 'Submit'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }

}
