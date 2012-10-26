<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\SystemConfigFormBase.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Full generic test suite for any form that data with the configuration system.
 *
 * @see UserAdminSettingsFormTest
 *   For a full working implementation.
 */
abstract class SystemConfigFormBase extends WebTestBase {
  /**
   * Form ID to use for testing.
   *
   * @var string
   */
  protected $form_id;

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

  public function setUp() {
    $this->values = array();
    parent::setUp();

    $this->setUpSystemConfigForm();
  }

  abstract function setUpSystemConfigForm();

  /**
   * Submit the system_config_form ensure the configuration has expected values.
   */
  function testConfigForm() {
    // Programmatically submit the given values.
    foreach ($this->values as $form_key => $data) {
      $values[$form_key] = $data['#value'];
    }
    $form_state = array('values' => $values);
    drupal_form_submit($this->form_id, $form_state);

    // Check that the form returns an error when expected, and vice versa.
    $errors = form_get_errors();
    $valid_form = empty($errors);
    $args = array(
      '%values' => print_r($values, TRUE),
      '%errors' => $valid_form ? t('None') : implode(' ', $errors),
    );
    $this->assertTrue($valid_form, format_string('Input values: %values<br/>Validation handler errors: %errors', $args));

    foreach ($this->values as $form_key => $data) {
      $this->assertEqual($data['#value'], config($data['#config_name'])->get($data['#config_key']));
    }
  }
}
