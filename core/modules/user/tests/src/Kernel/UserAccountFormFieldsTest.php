<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Verifies that the field order in user account forms is compatible with
 * password managers of web browsers.
 *
 * @group user
 */
class UserAccountFormFieldsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'user', 'field');

  /**
   * Tests the root user account form section in the "Configure site" form.
   */
  function testInstallConfigureForm() {
    require_once \Drupal::root() . '/core/includes/install.core.inc';
    require_once \Drupal::root() . '/core/includes/install.inc';
    $install_state = install_state_defaults();
    $form_state = new FormState();
    $form_state->addBuildInfo('args', [&$install_state]);
    $form = $this->container->get('form_builder')
      ->buildForm('Drupal\Core\Installer\Form\SiteConfigureForm', $form_state);

    // Verify name and pass field order.
    $this->assertFieldOrder($form['admin_account']['account']);

    // Verify that web browsers may autocomplete the email value and
    // autofill/prefill the name and pass values.
    foreach (array('mail', 'name', 'pass') as $key) {
      $this->assertFalse(isset($form['account'][$key]['#attributes']['autocomplete']), "'$key' field: 'autocomplete' attribute not found.");
    }
  }

  /**
   * Tests the user registration form.
   */
  function testUserRegistrationForm() {
    // Install default configuration; required for AccountFormController.
    $this->installConfig(array('user'));

    // Disable email confirmation to unlock the password field.
    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->save();

    $form = $this->buildAccountForm('register');

    // Verify name and pass field order.
    $this->assertFieldOrder($form['account']);

    // Verify that web browsers may autocomplete the email value and
    // autofill/prefill the name and pass values.
    foreach (array('mail', 'name', 'pass') as $key) {
      $this->assertFalse(isset($form['account'][$key]['#attributes']['autocomplete']), "'$key' field: 'autocomplete' attribute not found.");
    }
  }

  /**
   * Tests the user edit form.
   */
  function testUserEditForm() {
    // Install default configuration; required for AccountFormController.
    $this->installConfig(array('user'));

    // Install the router table and then rebuild.
    \Drupal::service('router.builder')->rebuild();

    $form = $this->buildAccountForm('default');

    // Verify name and pass field order.
    $this->assertFieldOrder($form['account']);

    // Verify that autocomplete is off on all account fields.
    foreach (array('mail', 'name', 'pass') as $key) {
      $this->assertIdentical($form['account'][$key]['#attributes']['autocomplete'], 'off', "'$key' field: 'autocomplete' attribute is 'off'.");
    }
  }

  /**
   * Asserts that the 'name' form element is directly before the 'pass' element.
   *
   * @param array $elements
   *   A form array section that contains the user account form elements.
   */
  protected function assertFieldOrder(array $elements) {
    $name_index = 0;
    $name_weight = 0;
    $pass_index = 0;
    $pass_weight = 0;
    $index = 0;
    foreach ($elements as $key => $element) {
      if ($key === 'name') {
        $name_index = $index;
        $name_weight = $element['#weight'];
        $this->assertTrue($element['#sorted'], "'name' field is #sorted.");
      }
      elseif ($key === 'pass') {
        $pass_index = $index;
        $pass_weight = $element['#weight'];
        $this->assertTrue($element['#sorted'], "'pass' field is #sorted.");
      }
      $index++;
    }
    $this->assertEqual($name_index, $pass_index - 1, "'name' field ($name_index) appears before 'pass' field ($pass_index).");
    $this->assertTrue($name_weight < $pass_weight, "'name' field weight ($name_weight) is smaller than 'pass' field weight ($pass_weight).");
  }

  /**
   * Builds the user account form for a given operation.
   *
   * @param string $operation
   *   The entity operation; one of 'register' or 'default'.
   *
   * @return array
   *   The form array.
   */
  protected function buildAccountForm($operation) {
    // @see HtmlEntityFormController::getFormObject()
    $entity_type = 'user';
    $fields = array();
    if ($operation != 'register') {
      $fields['uid'] = 2;
    }
    $entity = $this->container->get('entity.manager')
      ->getStorage($entity_type)
      ->create($fields);
    $this->container->get('entity.manager')
      ->getFormObject($entity_type, $operation)
      ->setEntity($entity);

    // @see EntityFormBuilder::getForm()
    return $this->container->get('entity.form_builder')->getForm($entity, $operation);
  }

}
