<?php

/**
 * @file
 * Contains \Drupal\form_test\Form\FormTestDisabledElementsForm.
 */

namespace Drupal\form_test\Form;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Builds a form to test disabled elements.
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
    foreach (array('textfield', 'textarea', 'search', 'tel', 'hidden') as $type) {
      $form[$type] = array(
        '#type' => $type,
        '#title' => $type,
        '#default_value' => $type,
        '#test_hijack_value' => 'HIJACK',
        '#disabled' => TRUE,
      );
    }

    // Multiple values option elements.
    foreach (array('checkboxes', 'select') as $type) {
      $form[$type . '_multiple'] = array(
        '#type' => $type,
        '#title' => $type . ' (multiple)',
        '#options' => array(
          'test_1' => 'Test 1',
          'test_2' => 'Test 2',
        ),
        '#multiple' => TRUE,
        '#default_value' => array('test_2' => 'test_2'),
        // The keys of #test_hijack_value need to match the #name of the control.
        // @see FormsTestCase::testDisabledElements()
        '#test_hijack_value' => $type == 'select' ? array('' => 'test_1') : array('test_1' => 'test_1'),
        '#disabled' => TRUE,
      );
    }

    // Single values option elements.
    foreach (array('radios', 'select') as $type) {
      $form[$type . '_single'] = array(
        '#type' => $type,
        '#title' => $type . ' (single)',
        '#options' => array(
          'test_1' => 'Test 1',
          'test_2' => 'Test 2',
        ),
        '#multiple' => FALSE,
        '#default_value' => 'test_2',
        '#test_hijack_value' => 'test_1',
        '#disabled' => TRUE,
      );
    }

    // Checkbox and radio.
    foreach (array('checkbox', 'radio') as $type) {
      $form[$type . '_unchecked'] = array(
        '#type' => $type,
        '#title' => $type . ' (unchecked)',
        '#return_value' => 1,
        '#default_value' => 0,
        '#test_hijack_value' => 1,
        '#disabled' => TRUE,
      );
      $form[$type . '_checked'] = array(
        '#type' => $type,
        '#title' => $type . ' (checked)',
        '#return_value' => 1,
        '#default_value' => 1,
        '#test_hijack_value' => NULL,
        '#disabled' => TRUE,
      );
    }

    // Weight, number, range.
    foreach (array('weight', 'number', 'range') as $type) {
      $form[$type] = array(
        '#type' => $type,
        '#title' => $type,
        '#default_value' => 10,
        '#test_hijack_value' => 5,
        '#disabled' => TRUE,
      );
    }

    // Color.
    $form['color'] = array(
      '#type' => 'color',
      '#title' => 'color',
      '#default_value' => '#0000ff',
      '#test_hijack_value' => '#ff0000',
      '#disabled' => TRUE,
    );

    // The #disabled state should propagate to children.
    $form['disabled_container'] = array(
      '#disabled' => TRUE,
    );
    foreach (array('textfield', 'textarea', 'hidden', 'tel', 'url') as $type) {
      $form['disabled_container']['disabled_container_' . $type] = array(
        '#type' => $type,
        '#title' => $type,
        '#default_value' => $type,
        '#test_hijack_value' => 'HIJACK',
      );
    }

    // Date.
    $date = new DrupalDateTime('1978-11-01 10:30:00', 'Europe/Berlin');
    // Starting with PHP 5.4.30, 5.5.15, JSON encoded DateTime objects include
    // microseconds. Make sure that the expected value is correct for all
    // versions by encoding and decoding it again instead of hardcoding it.
    // See https://github.com/php/php-src/commit/fdb2709dd27c5987c2d2c8aaf0cdbebf9f17f643
    $expected = json_decode(json_encode($date), TRUE);
    $form['disabled_container']['disabled_container_datetime'] = array(
      '#type' => 'datetime',
      '#title' => 'datetime',
      '#default_value' => $date,
      '#expected_value' => $expected,
      '#test_hijack_value' => new DrupalDateTime('1978-12-02 11:30:00', 'Europe/Berlin'),
      '#date_timezone' => 'Europe/Berlin',
    );

    $form['disabled_container']['disabled_container_date'] = array(
      '#type' => 'date',
      '#title' => 'date',
      '#default_value' => '2001-01-13',
      '#expected_value' => '2001-01-13',
      '#test_hijack_value' => '2013-01-01',
      '#date_timezone' => 'Europe/Berlin',
    );


    // Try to hijack the email field with a valid email.
    $form['disabled_container']['disabled_container_email'] = array(
      '#type' => 'email',
      '#title' => 'email',
      '#default_value' => 'foo@example.com',
      '#test_hijack_value' => 'bar@example.com',
    );

    // Try to hijack the URL field with a valid URL.
    $form['disabled_container']['disabled_container_url'] = array(
      '#type' => 'url',
      '#title' => 'url',
      '#default_value' => 'http://example.com',
      '#test_hijack_value' => 'http://example.com/foo',
    );

    // Text format.
    $form['text_format'] = array(
      '#type' => 'text_format',
      '#title' => 'Text format',
      '#disabled' => TRUE,
      '#default_value' => 'Text value',
      '#format' => 'plain_text',
      '#expected_value' => array(
        'value' => 'Text value',
        'format' => 'plain_text',
      ),
      '#test_hijack_value' => array(
        'value' => 'HIJACK',
        'format' => 'filtered_html',
      ),
    );

    // Password fields.
    $form['password'] = array(
      '#type' => 'password',
      '#title' => 'Password',
      '#disabled' => TRUE,
    );
    $form['password_confirm'] = array(
      '#type' => 'password_confirm',
      '#title' => 'Password confirm',
      '#disabled' => TRUE,
    );

    // Files.
    $form['file'] = array(
      '#type' => 'file',
      '#title' => 'File',
      '#disabled' => TRUE,
    );
    $form['managed_file'] = array(
      '#type' => 'managed_file',
      '#title' => 'Managed file',
      '#disabled' => TRUE,
    );

    // Buttons.
    $form['image_button'] = array(
      '#type' => 'image_button',
      '#value' => 'Image button',
      '#disabled' => TRUE,
    );
    $form['button'] = array(
      '#type' => 'button',
      '#value' => 'Button',
      '#disabled' => TRUE,
    );
    $form['submit_disabled'] = array(
      '#type' => 'submit',
      '#value' => 'Submit',
      '#disabled' => TRUE,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
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
