<?php

namespace Drupal\KernelTests\Core\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests form validation mesages are displayed in the same order as the fields.
 *
 * @group Form
 */
class FormValidationMessageOrderTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_validation_error_message_order_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Prepare fields with weights specified.
    $form['one'] = [
      '#type' => 'textfield',
      '#title' => 'One',
      '#required' => TRUE,
      '#weight' => 40,
    ];
    $form['two'] = [
      '#type' => 'textfield',
      '#title' => 'Two',
      '#required' => TRUE,
      '#weight' => 30,
    ];
    $form['three'] = [
      '#type' => 'textfield',
      '#title' => 'Three',
      '#required' => TRUE,
      '#weight' => 10,
    ];
    $form['four'] = [
      '#type' => 'textfield',
      '#title' => 'Four',
      '#required' => TRUE,
      '#weight' => 20,
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => 'Submit',
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Tests that fields validation messages are sorted in the fields order.
   */
  public function testLimitValidationErrors() {
    $form_state = new FormState();
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);

    $messages = drupal_get_messages();
    $this->assertTrue(isset($messages['error']));
    $error_messages = $messages['error'];
    $this->assertEqual($error_messages[0], 'Three field is required.');
    $this->assertEqual($error_messages[1], 'Four field is required.');
    $this->assertEqual($error_messages[2], 'Two field is required.');
    $this->assertEqual($error_messages[3], 'One field is required.');
  }

}
