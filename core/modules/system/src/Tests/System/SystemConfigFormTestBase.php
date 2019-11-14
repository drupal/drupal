<?php

namespace Drupal\system\Tests\System;

@trigger_error('\Drupal\system\Tests\System\SystemConfigFormTestBase is deprecated in Drupal 8.6.0 and will be removed before Drupal 9.0.0. Use \Drupal\KernelTests\ConfigFormTestBase instead.', E_USER_DEPRECATED);

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormState;
use Drupal\simpletest\WebTestBase;

/**
 * Full generic test suite for any form that data with the configuration system.
 *
 * @see UserAdminSettingsFormTest
 *   For a full working implementation.
 *
 * @deprecated in drupal:8.6.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\KernelTests\ConfigFormTestBase instead.
 *
 * @see https://www.drupal.org/node/2941907
 */
abstract class SystemConfigFormTestBase extends WebTestBase {
  /**
   * Form ID to use for testing.
   *
   * @var \Drupal\Core\Form\FormInterface
   */
  protected $form;

  /**
   * Values to use for testing.
   *
   * Contains details for form key, configuration object name, and config key.
   * Example:
   * @code
   *   array(
   *     'user_mail_cancel_confirm_body' => array(
   *       '#value' => $this->randomString(),
   *       '#config_name' => 'user.mail',
   *       '#config_key' => 'cancel_confirm.body',
   *     ),
   *   );
   * @endcode
   *
   * @var array
   */
  protected $values;

  /**
   * Submit the system_config_form ensure the configuration has expected values.
   */
  public function testConfigForm() {
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
    $args = [
      '%values' => print_r($values, TRUE),
      '%errors' => $valid_form ? t('None') : implode(' ', $errors),
    ];
    $this->assertTrue($valid_form, new FormattableMarkup('Input values: %values<br/>Validation handler errors: %errors', $args));

    foreach ($this->values as $data) {
      $this->assertEqual($data['#value'], $this->config($data['#config_name'])->get($data['#config_key']));
    }
  }

}
