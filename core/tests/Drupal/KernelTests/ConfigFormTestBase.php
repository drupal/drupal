<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\Core\Form\FormState;

/**
 * Full generic test suite for any form that data with the configuration system.
 *
 * @see UserAdminSettingsFormTest
 *   For a full working implementation.
 */
abstract class ConfigFormTestBase extends KernelTestBase {
  /**
   * Form ID to use for testing.
   *
   * @var \Drupal\Core\Form\FormInterface
   */
  protected $form;

  /**
   * Values to use for testing.
   *
   * @var array
   * Contains details for form key, configuration object name, and config key.
   * Example:
   * @code
   *   [
   *     'user_mail_cancel_confirm_body' => [
   *       '#value' => $this->randomString(),
   *       '#config_name' => 'user.mail',
   *       '#config_key' => 'cancel_confirm.body',
   *     ],
   *   ];
   * @endcode
   */
  protected $values;

  /**
   * Submit the system_config_form ensure the configuration has expected values.
   */
  public function testConfigForm(): void {
    // Programmatically submit the given values.
    $values = [];
    foreach ($this->values as $form_key => $data) {
      $values[$form_key] = $data['#value'];
    }
    $form_state = (new FormState())->setValues($values);
    \Drupal::formBuilder()->submitForm($this->form, $form_state);

    // Check that the form returns an error when expected, and vice versa.
    $errors = $form_state->getErrors();
    $valid_form = empty($errors);
    $values = print_r($values, TRUE);
    $errors = $valid_form ? t('None') : implode(' ', $errors);
    $this->assertTrue($valid_form, sprintf('Input values: %s<br/>Validation handler errors: %s', $values, $errors));
    foreach ($this->values as $data) {
      $this->assertEquals($this->config($data['#config_name'])->get($data['#config_key']), $data['#value']);
    }
  }

}
