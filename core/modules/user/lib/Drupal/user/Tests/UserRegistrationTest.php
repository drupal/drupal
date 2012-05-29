<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserRegistrationTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

class UserRegistrationTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User registration',
      'description' => 'Test registration of user under different configurations.',
      'group' => 'User'
    );
  }

  function setUp() {
    parent::setUp('field_test');
  }

  function testRegistrationWithEmailVerification() {
    // Require e-mail verification.
    variable_set('user_email_verification', TRUE);

    // Set registration to administrator only.
    variable_set('user_register', USER_REGISTER_ADMINISTRATORS_ONLY);
    $this->drupalGet('user/register');
    $this->assertResponse(403, t('Registration page is inaccessible when only administrators can create accounts.'));

    // Allow registration by site visitors without administrator approval.
    variable_set('user_register', USER_REGISTER_VISITORS);
    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertText(t('A welcome message with further instructions has been sent to your e-mail address.'), t('User registered successfully.'));
    $accounts = user_load_multiple(array(), array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertTrue($new_user->status, t('New account is active after registration.'));

    // Allow registration by site visitors, but require administrator approval.
    variable_set('user_register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL);
    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $accounts = user_load_multiple(array(), array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertFalse($new_user->status, t('New account is blocked until approved by an administrator.'));
  }

  function testRegistrationWithoutEmailVerification() {
    // Don't require e-mail verification.
    variable_set('user_email_verification', FALSE);

    // Allow registration by site visitors without administrator approval.
    variable_set('user_register', USER_REGISTER_VISITORS);
    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';

    // Try entering a mismatching password.
    $edit['pass[pass1]'] = '99999.0';
    $edit['pass[pass2]'] = '99999';
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertText(t('The specified passwords do not match.'), t('Typing mismatched passwords displays an error message.'));

    // Enter a correct password.
    $edit['pass[pass1]'] = $new_pass = $this->randomName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $accounts = user_load_multiple(array(), array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertText(t('Registration successful. You are now logged in.'), t('Users are logged in after registering.'));
    $this->drupalLogout();

    // Allow registration by site visitors, but require administrator approval.
    variable_set('user_register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL);
    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $edit['pass[pass1]'] = $pass = $this->randomName();
    $edit['pass[pass2]'] = $pass;
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertText(t('Thank you for applying for an account. Your account is currently pending approval by the site administrator.'), t('Users are notified of pending approval'));

    // Try to login before administrator approval.
    $auth = array(
      'name' => $name,
      'pass' => $pass,
    );
    $this->drupalPost('user/login', $auth, t('Log in'));
    $this->assertText(t('The username @name has not been activated or is blocked.', array('@name' => $name)), t('User cannot login yet.'));

    // Activate the new account.
    $accounts = user_load_multiple(array(), array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $admin_user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($admin_user);
    $edit = array(
      'status' => 1,
    );
    $this->drupalPost('user/' . $new_user->uid . '/edit', $edit, t('Save'));
    $this->drupalLogout();

    // Login after administrator approval.
    $this->drupalPost('user/login', $auth, t('Log in'));
    $this->assertText(t('Member for'), t('User can log in after administrator approval.'));
  }

  function testRegistrationEmailDuplicates() {
    // Don't require e-mail verification.
    variable_set('user_email_verification', FALSE);

    // Allow registration by site visitors without administrator approval.
    variable_set('user_register', USER_REGISTER_VISITORS);

    // Set up a user to check for duplicates.
    $duplicate_user = $this->drupalCreateUser();

    $edit = array();
    $edit['name'] = $this->randomName();
    $edit['mail'] = $duplicate_user->mail;

    // Attempt to create a new account using an existing e-mail address.
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertText(t('The e-mail address @email is already registered.', array('@email' => $duplicate_user->mail)), t('Supplying an exact duplicate email address displays an error message'));

    // Attempt to bypass duplicate email registration validation by adding spaces.
    $edit['mail'] = '   ' . $duplicate_user->mail . '   ';

    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertText(t('The e-mail address @email is already registered.', array('@email' => $duplicate_user->mail)), t('Supplying a duplicate email address with added whitespace displays an error message'));
  }

  function testRegistrationDefaultValues() {
    // Allow registration by site visitors without administrator approval.
    variable_set('user_register', USER_REGISTER_VISITORS);

    // Don't require e-mail verification.
    variable_set('user_email_verification', FALSE);

    // Set the default timezone to Brussels.
    variable_set('configurable_timezones', 1);
    variable_set('date_default_timezone', 'Europe/Brussels');

    // Check that the account information fieldset's options are not displayed
    // is a fieldset if there is not more than one fieldset in the form.
    $this->drupalGet('user/register');
    $this->assertNoRaw('<fieldset id="edit-account"><legend>Account information</legend>', t('Account settings fieldset was hidden.'));

    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $edit['pass[pass1]'] = $new_pass = $this->randomName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPost(NULL, $edit, t('Create new account'));

    // Check user fields.
    $accounts = user_load_multiple(array(), array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertEqual($new_user->name, $name, t('Username matches.'));
    $this->assertEqual($new_user->mail, $mail, t('E-mail address matches.'));
    $this->assertEqual($new_user->theme, '', t('Correct theme field.'));
    $this->assertEqual($new_user->signature, '', t('Correct signature field.'));
    $this->assertTrue(($new_user->created > REQUEST_TIME - 20 ), t('Correct creation time.'));
    $this->assertEqual($new_user->status, variable_get('user_register', USER_REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL) == USER_REGISTER_VISITORS ? 1 : 0, t('Correct status field.'));
    $this->assertEqual($new_user->timezone, variable_get('date_default_timezone'), t('Correct time zone field.'));
    $this->assertEqual($new_user->langcode, language_default()->langcode, t('Correct language field.'));
    $this->assertEqual($new_user->preferred_langcode, language_default()->langcode, t('Correct preferred language field.'));
    $this->assertEqual($new_user->picture, 0, t('Correct picture field.'));
    $this->assertEqual($new_user->init, $mail, t('Correct init field.'));
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

    // Check that the field does not appear on the registration form.
    $this->drupalGet('user/register');
    $this->assertNoText($instance['label'], t('The field does not appear on user registration form'));

    // Have the field appear on the registration form.
    $instance['settings']['user_register_form'] = TRUE;
    field_update_instance($instance);
    $this->drupalGet('user/register');
    $this->assertText($instance['label'], t('The field appears on user registration form'));

    // Check that validation errors are correctly reported.
    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    // Missing input in required field.
    $edit['test_user_field[und][0][value]'] = '';
    $this->drupalPost(NULL, $edit, t('Create new account'));
    $this->assertRaw(t('@name field is required.', array('@name' => $instance['label'])), t('Field validation error was correctly reported.'));
    // Invalid input.
    $edit['test_user_field[und][0][value]'] = '-1';
    $this->drupalPost(NULL, $edit, t('Create new account'));
    $this->assertRaw(t('%name does not accept the value -1.', array('%name' => $instance['label'])), t('Field validation error was correctly reported.'));

    // Submit with valid data.
    $value = rand(1, 255);
    $edit['test_user_field[und][0][value]'] = $value;
    $this->drupalPost(NULL, $edit, t('Create new account'));
    // Check user fields.
    $accounts = user_load_multiple(array(), array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertEqual($new_user->test_user_field[LANGUAGE_NOT_SPECIFIED][0]['value'], $value, t('The field value was correclty saved.'));

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
      $accounts = user_load_multiple(array(), array('name' => $name, 'mail' => $mail));
      $new_user = reset($accounts);
      $this->assertEqual($new_user->test_user_field[LANGUAGE_NOT_SPECIFIED][0]['value'], $value, t('@js : The field value was correclty saved.', array('@js' => $js)));
      $this->assertEqual($new_user->test_user_field[LANGUAGE_NOT_SPECIFIED][1]['value'], $value + 1, t('@js : The field value was correclty saved.', array('@js' => $js)));
      $this->assertEqual($new_user->test_user_field[LANGUAGE_NOT_SPECIFIED][2]['value'], $value + 2, t('@js : The field value was correclty saved.', array('@js' => $js)));
    }
  }
}
