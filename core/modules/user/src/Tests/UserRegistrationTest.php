<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserRegistrationTest.
 */

namespace Drupal\user\Tests;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests registration of user under different configurations.
 *
 * @group user
 */
class UserRegistrationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test');

  function testRegistrationWithEmailVerification() {
    $config = $this->config('user.settings');
    // Require email verification.
    $config->set('verify_mail', TRUE)->save();

    // Set registration to administrator only.
    $config->set('register', USER_REGISTER_ADMINISTRATORS_ONLY)->save();
    $this->drupalGet('user/register');
    $this->assertResponse(403, 'Registration page is inaccessible when only administrators can create accounts.');

    // Allow registration by site visitors without administrator approval.
    $config->set('register', USER_REGISTER_VISITORS)->save();
    $edit = array();
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('A welcome message with further instructions has been sent to your email address.'), 'User registered successfully.');
    $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertTrue($new_user->isActive(), 'New account is active after registration.');

    // Allow registration by site visitors, but require administrator approval.
    $config->set('register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)->save();
    $edit = array();
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->container->get('entity.manager')->getStorage('user')->resetCache();
    $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertFalse($new_user->isActive(), 'New account is blocked until approved by an administrator.');
  }

  function testRegistrationWithoutEmailVerification() {
    $config = $this->config('user.settings');
    // Don't require email verification and allow registration by site visitors
    // without administrator approval.
    $config
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();

    $edit = array();
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';

    // Try entering a mismatching password.
    $edit['pass[pass1]'] = '99999.0';
    $edit['pass[pass2]'] = '99999';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('The specified passwords do not match.'), 'Typing mismatched passwords displays an error message.');

    // Enter a correct password.
    $edit['pass[pass1]'] = $new_pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->container->get('entity.manager')->getStorage('user')->resetCache();
    $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertNotNull($new_user, 'New account successfully created with matching passwords.');
    $this->assertText(t('Registration successful. You are now logged in.'), 'Users are logged in after registering.');
    $this->drupalLogout();

    // Allow registration by site visitors, but require administrator approval.
    $config->set('register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)->save();
    $edit = array();
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $edit['pass[pass1]'] = $pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $pass;
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('Thank you for applying for an account. Your account is currently pending approval by the site administrator.'), 'Users are notified of pending approval');

    // Try to login before administrator approval.
    $auth = array(
      'name' => $name,
      'pass' => $pass,
    );
    $this->drupalPostForm('user/login', $auth, t('Log in'));
    $this->assertText(t('The username @name has not been activated or is blocked.', array('@name' => $name)), 'User cannot login yet.');

    // Activate the new account.
    $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $admin_user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($admin_user);
    $edit = array(
      'status' => 1,
    );
    $this->drupalPostForm('user/' . $new_user->id() . '/edit', $edit, t('Save'));
    $this->drupalLogout();

    // Login after administrator approval.
    $this->drupalPostForm('user/login', $auth, t('Log in'));
    $this->assertText(t('Member for'), 'User can log in after administrator approval.');
  }

  function testRegistrationEmailDuplicates() {
    // Don't require email verification and allow registration by site visitors
    // without administrator approval.
    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();

    // Set up a user to check for duplicates.
    $duplicate_user = $this->drupalCreateUser();

    $edit = array();
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $duplicate_user->getEmail();

    // Attempt to create a new account using an existing email address.
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('The email address @email is already registered.', array('@email' => $duplicate_user->getEmail())), 'Supplying an exact duplicate email address displays an error message');

    // Attempt to bypass duplicate email registration validation by adding spaces.
    $edit['mail'] = '   ' . $duplicate_user->getEmail() . '   ';

    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('The email address @email is already registered.', array('@email' => $duplicate_user->getEmail())), 'Supplying a duplicate email address with added whitespace displays an error message');
  }

  function testRegistrationDefaultValues() {
    // Don't require email verification and allow registration by site visitors
    // without administrator approval.
    $config_user_settings = $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();

    // Set the default timezone to Brussels.
    $config_system_date = $this->config('system.date')
      ->set('timezone.user.configurable', 1)
      ->set('timezone.default', 'Europe/Brussels')
      ->save();

    // Check that the account information options are not displayed
    // as a details element if there is not more than one details in the form.
    $this->drupalGet('user/register');
    $this->assertNoRaw('<details id="edit-account"><summary>Account information</summary>');

    $edit = array();
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $edit['pass[pass1]'] = $new_pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPostForm(NULL, $edit, t('Create new account'));

    // Check user fields.
    $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertEqual($new_user->getUsername(), $name, 'Username matches.');
    $this->assertEqual($new_user->getEmail(), $mail, 'Email address matches.');
    $this->assertEqual($new_user->getSignature(), '', 'Correct signature field.');
    $this->assertTrue(($new_user->getCreatedTime() > REQUEST_TIME - 20 ), 'Correct creation time.');
    $this->assertEqual($new_user->isActive(), $config_user_settings->get('register') == USER_REGISTER_VISITORS ? 1 : 0, 'Correct status field.');
    $this->assertEqual($new_user->getTimezone(), $config_system_date->get('timezone.default'), 'Correct time zone field.');
    $this->assertEqual($new_user->langcode->value, \Drupal::languageManager()->getDefaultLanguage()->getId(), 'Correct language field.');
    $this->assertEqual($new_user->preferred_langcode->value, \Drupal::languageManager()->getDefaultLanguage()->getId(), 'Correct preferred language field.');
    $this->assertEqual($new_user->init->value, $mail, 'Correct init field.');
  }

  /**
   * Tests Field API fields on user registration forms.
   */
  function testRegistrationWithUserFields() {
    // Create a field on 'user' entity type.
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => 'test_user_field',
      'entity_type' => 'user',
      'type' => 'test_field',
      'cardinality' => 1,
    ));
    $field_storage->save();
    $field = entity_create('field_config', array(
      'field_storage' => $field_storage,
      'label' => 'Some user field',
      'bundle' => 'user',
      'required' => TRUE,
    ));
    $field->save();
    entity_get_form_display('user', 'user', 'default')
      ->setComponent('test_user_field', array('type' => 'test_field_widget'))
      ->save();
    entity_get_form_display('user', 'user', 'register')
      ->save();

    // Check that the field does not appear on the registration form.
    $this->drupalGet('user/register');
    $this->assertNoText($field->label(), 'The field does not appear on user registration form');

    // Have the field appear on the registration form.
    entity_get_form_display('user', 'user', 'register')
      ->setComponent('test_user_field', array('type' => 'test_field_widget'))
      ->save();

    $this->drupalGet('user/register');
    $this->assertText($field->label(), 'The field appears on user registration form');

    // Check that validation errors are correctly reported.
    $edit = array();
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    // Missing input in required field.
    $edit['test_user_field[0][value]'] = '';
    $this->drupalPostForm(NULL, $edit, t('Create new account'));
    $this->assertRaw(t('@name field is required.', array('@name' => $field->label())), 'Field validation error was correctly reported.');
    // Invalid input.
    $edit['test_user_field[0][value]'] = '-1';
    $this->drupalPostForm(NULL, $edit, t('Create new account'));
    $this->assertRaw(t('%name does not accept the value -1.', array('%name' => $field->label())), 'Field validation error was correctly reported.');

    // Submit with valid data.
    $value = rand(1, 255);
    $edit['test_user_field[0][value]'] = $value;
    $this->drupalPostForm(NULL, $edit, t('Create new account'));
    // Check user fields.
    $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertEqual($new_user->test_user_field->value, $value, 'The field value was correclty saved.');

    // Check that the 'add more' button works.
    $field_storage->cardinality = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED;
    $field_storage->save();
    foreach (array('js', 'nojs') as $js) {
      $this->drupalGet('user/register');
      // Add two inputs.
      $value = rand(1, 255);
      $edit = array();
      $edit['test_user_field[0][value]'] = $value;
      if ($js == 'js') {
        $this->drupalPostAjaxForm(NULL, $edit, 'test_user_field_add_more');
        $this->drupalPostAjaxForm(NULL, $edit, 'test_user_field_add_more');
      }
      else {
        $this->drupalPostForm(NULL, $edit, t('Add another item'));
        $this->drupalPostForm(NULL, $edit, t('Add another item'));
      }
      // Submit with three values.
      $edit['test_user_field[1][value]'] = $value + 1;
      $edit['test_user_field[2][value]'] = $value + 2;
      $edit['name'] = $name = $this->randomMachineName();
      $edit['mail'] = $mail = $edit['name'] . '@example.com';
      $this->drupalPostForm(NULL, $edit, t('Create new account'));
      // Check user fields.
      $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
      $new_user = reset($accounts);
      $this->assertEqual($new_user->test_user_field[0]->value, $value, format_string('@js : The field value was correclty saved.', array('@js' => $js)));
      $this->assertEqual($new_user->test_user_field[1]->value, $value + 1, format_string('@js : The field value was correclty saved.', array('@js' => $js)));
      $this->assertEqual($new_user->test_user_field[2]->value, $value + 2, format_string('@js : The field value was correclty saved.', array('@js' => $js)));
    }
  }

}
