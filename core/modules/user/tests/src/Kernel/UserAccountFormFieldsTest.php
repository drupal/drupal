<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Verifies the field order in user account forms.
 *
 * @group user
 */
class UserAccountFormFieldsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'field'];

  /**
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $user;

  /**
   * Tests the root user account form section in the "Configure site" form.
   */
  public function testInstallConfigureForm(): void {
    require_once $this->root . '/core/includes/install.core.inc';
    require_once $this->root . '/core/includes/install.inc';
    $install_state = install_state_defaults();
    $form_state = new FormState();
    $form_state->addBuildInfo('args', [&$install_state]);
    $form = $this->container->get('form_builder')
      ->buildForm('Drupal\Core\Installer\Form\SiteConfigureForm', $form_state);

    // Verify name and pass field order.
    $this->assertFieldOrder($form['admin_account']['account']);

    // Verify that web browsers may autocomplete the email value and
    // autofill/prefill the name and pass values.
    foreach (['mail', 'name', 'pass'] as $key) {
      $this->assertFalse(isset($form['account'][$key]['#attributes']['autocomplete']), "'$key' field: 'autocomplete' attribute not found.");
    }
  }

  /**
   * Tests the user registration form.
   */
  public function testUserRegistrationForm(): void {
    // Install default configuration; required for AccountFormController.
    $this->installConfig(['user']);

    // Disable email confirmation to unlock the password field.
    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->save();

    $form = $this->buildAccountForm('register');

    // Verify name and pass field order.
    $this->assertFieldOrder($form['account']);

    // Verify that web browsers may autocomplete the email value and
    // autofill/prefill the name and pass values.
    foreach (['mail', 'name', 'pass'] as $key) {
      $this->assertFalse(isset($form['account'][$key]['#attributes']['autocomplete']), "'$key' field: 'autocomplete' attribute not found.");
    }
  }

  /**
   * Tests the user edit form.
   */
  public function testUserEditForm(): void {
    // Install default configuration; required for AccountFormController.
    $this->installConfig(['user']);
    $this->installEntitySchema('user');

    $this->user = User::create(['name' => 'test']);
    $this->user->save();

    $form = $this->buildAccountForm('default');

    // Verify name and pass field order.
    $this->assertFieldOrder($form['account']);

    // Verify that autocomplete is off on all account fields.
    foreach (['mail', 'name', 'pass'] as $key) {
      $this->assertSame('off', $form['account'][$key]['#attributes']['autocomplete'], "'{$key}' field: 'autocomplete' attribute is 'off'.");
    }
  }

  /**
   * Asserts that the 'name' form element is directly before the 'pass' element.
   *
   * @param array $elements
   *   A form array section that contains the user account form elements.
   *
   * @internal
   */
  protected function assertFieldOrder(array $elements): void {
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
    $this->assertEquals($pass_index - 1, $name_index, "'name' field ({$name_index}) appears before 'pass' field ({$pass_index}).");
    $this->assertLessThan($pass_weight, $name_weight, "'name' field weight ($name_weight) should be smaller than 'pass' field weight ($pass_weight).");
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
    if ($operation != 'register') {
      // Use an existing user.
      $entity = $this->user;
    }
    else {
      $entity = $this->container->get('entity_type.manager')
        ->getStorage($entity_type)
        ->create();
    }

    // @see EntityFormBuilder::getForm()
    return $this->container->get('entity.form_builder')->getForm($entity, $operation);
  }

}
