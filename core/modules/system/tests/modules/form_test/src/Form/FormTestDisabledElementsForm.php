<?php

namespace Drupal\form_test\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Builds a form to test disabled elements.
 *
 * @internal
 */
class FormTestDisabledElementsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_form_test_disabled_elements';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Elements that take a simple default value.
    foreach (['textfield', 'textarea', 'search', 'tel', 'hidden'] as $type) {
      $form[$type] = [
        '#type' => $type,
        '#title' => $type,
        '#default_value' => $type,
        '#test_hijack_value' => 'HIJACK',
        '#disabled' => TRUE,
      ];
    }

    // Multiple values option elements.
    foreach (['checkboxes', 'select'] as $type) {
      $form[$type . '_multiple'] = [
        '#type' => $type,
        '#title' => $type . ' (multiple)',
        '#options' => [
          'test_1' => 'Test 1',
          'test_2' => 'Test 2',
        ],
        '#multiple' => TRUE,
        '#default_value' => ['test_2' => 'test_2'],
        // The keys of #test_hijack_value need to match the #name of the control.
        // @see FormsTestCase::testDisabledElements()
        '#test_hijack_value' => $type == 'select' ? ['' => 'test_1'] : ['test_1' => 'test_1'],
        '#disabled' => TRUE,
      ];
    }

    // Single values option elements.
    foreach (['radios', 'select'] as $type) {
      $form[$type . '_single'] = [
        '#type' => $type,
        '#title' => $type . ' (single)',
        '#options' => [
          'test_1' => 'Test 1',
          'test_2' => 'Test 2',
        ],
        '#multiple' => FALSE,
        '#default_value' => 'test_2',
        '#test_hijack_value' => 'test_1',
        '#disabled' => TRUE,
      ];
    }

    // Checkbox and radio.
    foreach (['checkbox', 'radio'] as $type) {
      $form[$type . '_unchecked'] = [
        '#type' => $type,
        '#title' => $type . ' (unchecked)',
        '#return_value' => 1,
        '#default_value' => 0,
        '#test_hijack_value' => 1,
        '#disabled' => TRUE,
      ];
      $form[$type . '_checked'] = [
        '#type' => $type,
        '#title' => $type . ' (checked)',
        '#return_value' => 1,
        '#default_value' => 1,
        '#test_hijack_value' => NULL,
        '#disabled' => TRUE,
      ];
    }

    // Weight, number, range.
    foreach (['weight', 'number', 'range'] as $type) {
      $form[$type] = [
        '#type' => $type,
        '#title' => $type,
        '#default_value' => 10,
        '#test_hijack_value' => 5,
        '#disabled' => TRUE,
      ];
    }

    // Color.
    $form['color'] = [
      '#type' => 'color',
      '#title' => 'color',
      '#default_value' => '#0000ff',
      '#test_hijack_value' => '#ff0000',
      '#disabled' => TRUE,
    ];

    // The #disabled state should propagate to children.
    $form['disabled_container'] = [
      '#disabled' => TRUE,
    ];
    foreach (['textfield', 'textarea', 'hidden', 'tel', 'url'] as $type) {
      $form['disabled_container']['disabled_container_' . $type] = [
        '#type' => $type,
        '#title' => $type,
        '#default_value' => $type,
        '#test_hijack_value' => 'HIJACK',
      ];
    }

    // Date.
    $date = new DrupalDateTime('1978-11-01 10:30:00', 'Europe/Berlin');
    // Starting with PHP 5.4.30, 5.5.15, JSON encoded DateTime objects include
    // microseconds. Make sure that the expected value is correct for all
    // versions by encoding and decoding it again instead of hardcoding it.
    // See https://github.com/php/php-src/commit/fdb2709dd27c5987c2d2c8aaf0cdbebf9f17f643
    $expected = json_decode(json_encode($date), TRUE);
    $form['disabled_container']['disabled_container_datetime'] = [
      '#type' => 'datetime',
      '#title' => 'datetime',
      '#default_value' => $date,
      '#expected_value' => $expected,
      '#test_hijack_value' => new DrupalDateTime('1978-12-02 11:30:00', 'Europe/Berlin'),
      '#date_timezone' => 'Europe/Berlin',
    ];

    $form['disabled_container']['disabled_container_date'] = [
      '#type' => 'date',
      '#title' => 'date',
      '#default_value' => '2001-01-13',
      '#expected_value' => '2001-01-13',
      '#test_hijack_value' => '2013-01-01',
      '#date_timezone' => 'Europe/Berlin',
    ];

    // Try to hijack the email field with a valid email.
    $form['disabled_container']['disabled_container_email'] = [
      '#type' => 'email',
      '#title' => 'email',
      '#default_value' => 'foo@example.com',
      '#test_hijack_value' => 'bar@example.com',
    ];

    // Try to hijack the URL field with a valid URL.
    $form['disabled_container']['disabled_container_url'] = [
      '#type' => 'url',
      '#title' => 'url',
      '#default_value' => 'http://example.com',
      '#test_hijack_value' => 'http://example.com/foo',
    ];

    // Text format.
    $form['text_format'] = [
      '#type' => 'text_format',
      '#title' => 'Text format',
      '#disabled' => TRUE,
      '#default_value' => 'Text value',
      '#format' => 'plain_text',
      '#expected_value' => [
        'value' => 'Text value',
        'format' => 'plain_text',
      ],
      '#test_hijack_value' => [
        'value' => 'HIJACK',
        'format' => 'filtered_html',
      ],
    ];

    // Password fields.
    $form['password'] = [
      '#type' => 'password',
      '#title' => 'Password',
      '#disabled' => TRUE,
    ];
    $form['password_confirm'] = [
      '#type' => 'password_confirm',
      '#title' => 'Password confirm',
      '#disabled' => TRUE,
    ];

    // Files.
    $form['file'] = [
      '#type' => 'file',
      '#title' => 'File',
      '#disabled' => TRUE,
    ];
    $form['managed_file'] = [
      '#type' => 'managed_file',
      '#title' => 'Managed file',
      '#disabled' => TRUE,
    ];

    // Buttons.
    $form['image_button'] = [
      '#type' => 'image_button',
      '#value' => 'Image button',
      '#disabled' => TRUE,
    ];
    $form['button'] = [
      '#type' => 'button',
      '#value' => 'Button',
      '#disabled' => TRUE,
    ];
    $form['submit_disabled'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
      '#disabled' => TRUE,
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
    $form_state->setResponse(new JsonResponse($form_state->getValues()));
  }

}
