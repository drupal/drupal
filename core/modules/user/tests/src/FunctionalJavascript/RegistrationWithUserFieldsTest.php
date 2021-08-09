<?php

namespace Drupal\Tests\user\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests user registration forms with additional fields.
 *
 * @group user
 */
class RegistrationWithUserFieldsTest extends WebDriverTestBase {

  /**
   * WebAssert object.
   *
   * @var \Drupal\Tests\WebAssert
   */
  protected $webAssert;

  /**
   * DocumentElement object.
   *
   * @var \Behat\Mink\Element\DocumentElement
   */
  protected $page;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->page = $this->getSession()->getPage();
    $this->webAssert = $this->assertSession();
  }

  /**
   * Tests Field API fields on user registration forms.
   */
  public function testRegistrationWithUserFields() {
    // Create a field on 'user' entity type.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'test_user_field',
      'entity_type' => 'user',
      'type' => 'test_field',
      'cardinality' => 1,
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'label' => 'Some user field',
      'bundle' => 'user',
      'required' => TRUE,
    ]);
    $field->save();

    \Drupal::service('entity_display.repository')->getFormDisplay('user', 'user', 'default')
      ->setComponent('test_user_field', ['type' => 'test_field_widget'])
      ->save();
    $user_registration_form = \Drupal::service('entity_display.repository')->getFormDisplay('user', 'user', 'register');
    $user_registration_form->save();

    // Check that the field does not appear on the registration form.
    $this->drupalGet('user/register');
    $this->webAssert->pageTextNotContains($field->label());

    // Have the field appear on the registration form.
    $user_registration_form->setComponent('test_user_field', ['type' => 'test_field_widget'])->save();

    $this->drupalGet('user/register');
    $this->webAssert->pageTextContains($field->label());

    // In order to check the server side validation the native browser
    // validation for required fields needs to be circumvented.
    $session = $this->getSession();
    $session->executeScript("jQuery('#edit-test-user-field-0-value').prop('required', false);");

    // Check that validation errors are correctly reported.
    $name = $this->randomMachineName();
    $this->page->fillField('edit-name', $name);
    $this->page->fillField('edit-mail', $name . '@example.com');

    $this->page->pressButton('edit-submit');
    $this->webAssert->pageTextContains(t('@name field is required.', ['@name' => $field->label()]));

    // Invalid input.
    $this->page->fillField('edit-test-user-field-0-value', '-1');
    $this->page->pressButton('edit-submit');
    $this->webAssert->pageTextContains($field->label() . ' does not accept the value -1.');

    // Submit with valid data.
    $value = (string) mt_rand(1, 255);
    $this->page->fillField('edit-test-user-field-0-value', $value);
    $this->page->pressButton('edit-submit');
    // Check user fields.
    $accounts = $this->container->get('entity_type.manager')->getStorage('user')
      ->loadByProperties(['name' => $name, 'mail' => $name . '@example.com']);
    $new_user = reset($accounts);
    $this->assertEquals($value, $new_user->test_user_field->value, 'The field value was correctly saved.');

    // Check that the 'add more' button works.
    $field_storage->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $field_storage->save();
    $name = $this->randomMachineName();
    $this->drupalGet('user/register');
    $this->page->fillField('edit-name', $name);
    $this->page->fillField('edit-mail', $name . '@example.com');
    $this->page->fillField('test_user_field[0][value]', $value);
    // Add two inputs.
    $this->page->pressButton('test_user_field_add_more');
    $this->webAssert->waitForElement('css', 'input[name="test_user_field[1][value]"]');
    $this->page->fillField('test_user_field[1][value]', $value . '1');
    $this->page->pressButton('test_user_field_add_more');
    $this->webAssert->waitForElement('css', 'input[name="test_user_field[2][value]"]');
    $this->page->fillField('test_user_field[2][value]', $value . '2');

    // Submit with three values.
    $this->page->pressButton('edit-submit');

    // Check user fields.
    $accounts = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->loadByProperties(['name' => $name, 'mail' => $name . '@example.com']);
    $new_user = reset($accounts);
    $this->assertEquals($value, $new_user->test_user_field[0]->value);
    $this->assertEquals($value . '1', $new_user->test_user_field[1]->value);
    $this->assertEquals($value . '2', $new_user->test_user_field[2]->value);
  }

}
