<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserRegistrationTest.
 */

namespace Drupal\user\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

class UserRegistrationTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test');

  public static function getInfo() {
    return array(
      'name' => 'User registration',
      'description' => 'Test registration of user under different configurations.',
      'group' => 'User'
    );
  }

  function testRegistrationWithEmailVerification() {
    $config = config('user.settings');
    // Require e-mail verification.
    $config->set('verify_mail', TRUE)->save();

    // Set registration to administrator only.
    $config->set('register', USER_REGISTER_ADMINISTRATORS_ONLY)->save();
    $this->drupalGet('user/register');
    $this->assertResponse(403, 'Registration page is inaccessible when only administrators can create accounts.');

    // Allow registration by site visitors without administrator approval.
    $config->set('register', USER_REGISTER_VISITORS)->save();
    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertText(t('A welcome message with further instructions has been sent to your e-mail address.'), 'User registered successfully.');
    $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertTrue($new_user->status->value, 'New account is active after registration.');

    // Allow registration by site visitors, but require administrator approval.
    $config->set('register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)->save();
    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->container->get('plugin.manager.entity')->getStorageController('user')->resetCache();
    $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertFalse($new_user->status->value, 'New account is blocked until approved by an administrator.');
  }

  function testRegistrationWithoutEmailVerification() {
    $config = config('user.settings');
    // Don't require e-mail verification and allow registration by site visitors
    // without administrator approval.
    $config
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();

    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';

    // Try entering a mismatching password.
    $edit['pass[pass1]'] = '99999.0';
    $edit['pass[pass2]'] = '99999';
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertText(t('The specified passwords do not match.'), 'Typing mismatched passwords displays an error message.');

    // Enter a correct password.
    $edit['pass[pass1]'] = $new_pass = $this->randomName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->container->get('plugin.manager.entity')->getStorageController('user')->resetCache();
    $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertText(t('Registration successful. You are now logged in.'), 'Users are logged in after registering.');
    $this->drupalLogout();

    // Allow registration by site visitors, but require administrator approval.
    $config->set('register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)->save();
    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $edit['pass[pass1]'] = $pass = $this->randomName();
    $edit['pass[pass2]'] = $pass;
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertText(t('Thank you for applying for an account. Your account is currently pending approval by the site administrator.'), 'Users are notified of pending approval');

    // Try to login before administrator approval.
    $auth = array(
      'name' => $name,
      'pass' => $pass,
    );
    $this->drupalPost('user/login', $auth, t('Log in'));
    $this->assertText(t('The username @name has not been activated or is blocked.', array('@name' => $name)), 'User cannot login yet.');

    // Activate the new account.
    $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $admin_user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($admin_user);
    $edit = array(
      'status' => 1,
    );
    $this->drupalPost('user/' . $new_user->id() . '/edit', $edit, t('Save'));
    $this->drupalLogout();

    // Login after administrator approval.
    $this->drupalPost('user/login', $auth, t('Log in'));
    $this->assertText(t('Member for'), 'User can log in after administrator approval.');
  }

  function testRegistrationEmailDuplicates() {
    // Don't require e-mail verification and allow registration by site visitors
    // without administrator approval.
    config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();

    // Set up a user to check for duplicates.
    $duplicate_user = $this->drupalCreateUser();

    $edit = array();
    $edit['name'] = $this->randomName();
    $edit['mail'] = $duplicate_user->mail;

    // Attempt to create a new account using an existing e-mail address.
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertText(t('The e-mail address @email is already registered.', array('@email' => $duplicate_user->mail)), 'Supplying an exact duplicate email address displays an error message');

    // Attempt to bypass duplicate email registration validation by adding spaces.
    $edit['mail'] = '   ' . $duplicate_user->mail . '   ';

    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertText(t('The e-mail address @email is already registered.', array('@email' => $duplicate_user->mail)), 'Supplying a duplicate email address with added whitespace displays an error message');
  }

  function testRegistrationDefaultValues() {
    // Don't require e-mail verification and allow registration by site visitors
    // without administrator approval.
    $config_user_settings = config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', USER_REGISTER_VISITORS)
      ->save();

    // Set the default timezone to Brussels.
    $config_system_timezone = config('system.timezone')
      ->set('user.configurable', 1)
      ->set('default', 'Europe/Brussels')
      ->save();

    // Check that the account information options are not displayed
    // as a details element if there is not more than one details in the form.
    $this->drupalGet('user/register');
    $this->assertNoRaw('<details id="edit-account"><summary>Account information</summary>');

    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $edit['pass[pass1]'] = $new_pass = $this->randomName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPost(NULL, $edit, t('Create new account'));

    // Check user fields.
    $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertEqual($new_user->name->value, $name, 'Username matches.');
    $this->assertEqual($new_user->mail->value, $mail, 'E-mail address matches.');
    $this->assertEqual($new_user->theme->value, '', 'Correct theme field.');
    $this->assertEqual($new_user->signature->value, '', 'Correct signature field.');
    $this->assertTrue(($new_user->created->value > REQUEST_TIME - 20 ), 'Correct creation time.');
    $this->assertEqual($new_user->status->value, $config_user_settings->get('register') == USER_REGISTER_VISITORS ? 1 : 0, 'Correct status field.');
    $this->assertEqual($new_user->timezone->value, $config_system_timezone->get('default'), 'Correct time zone field.');
    $this->assertEqual($new_user->langcode->value, language_default()->langcode, 'Correct language field.');
    $this->assertEqual($new_user->preferred_langcode->value, language_default()->langcode, 'Correct preferred language field.');
    $this->assertEqual($new_user->init->value, $mail, 'Correct init field.');
  }

  /**
   * Tests Field API fields on user registration forms.
   */
  function testRegistrationWithUserFields() {
    // Create a field, and an instance on 'user' entity type.
    $field = array(
      'type' => 'test_field',
      'field_name' => 'test_user_field',
      'cardinality' => 1,
    );
    field_create_field($field);
    $instance = array(
      'field_name' => 'test_user_field',
      'entity_type' => 'user',
      'label' => 'Some user field',
      'bundle' => 'user',
      'required' => TRUE,
      'settings' => array('user_register_form' => FALSE),
    );
    field_create_instance($instance);
    entity_get_form_display('user', 'user', 'default')
      ->setComponent('test_user_field', array('type' => 'test_field_widget'))
      ->save();

    // Check that the field does not appear on the registration form.
    $this->drupalGet('user/register');
    $this->assertNoText($instance['label'], 'The field does not appear on user registration form');

    // Have the field appear on the registration form.
    $instance['settings']['user_register_form'] = TRUE;
    field_update_instance($instance);
    $this->drupalGet('user/register');
    $this->assertText($instance['label'], 'The field appears on user registration form');

    // Check that validation errors are correctly reported.
    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    // Missing input in required field.
    $edit['test_user_field[und][0][value]'] = '';
    $this->drupalPost(NULL, $edit, t('Create new account'));
    $this->assertRaw(t('@name field is required.', array('@name' => $instance['label'])), 'Field validation error was correctly reported.');
    // Invalid input.
    $edit['test_user_field[und][0][value]'] = '-1';
    $this->drupalPost(NULL, $edit, t('Create new account'));
    $this->assertRaw(t('%name does not accept the value -1.', array('%name' => $instance['label'])), 'Field validation error was correctly reported.');

    // Submit with valid data.
    $value = rand(1, 255);
    $edit['test_user_field[und][0][value]'] = $value;
    $this->drupalPost(NULL, $edit, t('Create new account'));
    // Check user fields.
    $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertEqual($new_user->test_user_field->value, $value, 'The field value was correclty saved.');

    // Check that the 'add more' button works.
    $field['cardinality'] = FIELD_CARDINALITY_UNLIMITED;
    field_update_field($field);
    foreach (array('js', 'nojs') as $js) {
      $this->drupalGet('user/register');
      // Add two inputs.
      $value = rand(1, 255);
      $edit = array();
      $edit['test_user_field[und][0][value]'] = $value;
      if ($js == 'js') {
        $this->drupalPostAJAX(NULL, $edit, 'test_user_field_add_more');
        $this->drupalPostAJAX(NULL, $edit, 'test_user_field_add_more');
      }
      else {
        $this->drupalPost(NULL, $edit, t('Add another item'));
        $this->drupalPost(NULL, $edit, t('Add another item'));
      }
      // Submit with three values.
      $edit['test_user_field[und][1][value]'] = $value + 1;
      $edit['test_user_field[und][2][value]'] = $value + 2;
      $edit['name'] = $name = $this->randomName();
      $edit['mail'] = $mail = $edit['name'] . '@example.com';
      $this->drupalPost(NULL, $edit, t('Create new account'));
      // Check user fields.
      $accounts = entity_load_multiple_by_properties('user', array('name' => $name, 'mail' => $mail));
      $new_user = reset($accounts);
      $this->assertEqual($new_user->test_user_field[0]->value, $value, format_string('@js : The field value was correclty saved.', array('@js' => $js)));
      $this->assertEqual($new_user->test_user_field[1]->value, $value + 1, format_string('@js : The field value was correclty saved.', array('@js' => $js)));
      $this->assertEqual($new_user->test_user_field[2]->value, $value + 2, format_string('@js : The field value was correclty saved.', array('@js' => $js)));
    }
  }
}
