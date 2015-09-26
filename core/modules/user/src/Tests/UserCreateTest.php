<?php

/**
 * @file
 * Contains \Drupal\user\Tests\UserCreateTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the create user administration page.
 *
 * @group user
 */
class UserCreateTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('image');

  /**
   * Create a user through the administration interface and ensure that it
   * displays in the user list.
   */
  public function testUserAdd() {
    $user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($user);

    $this->assertEqual($user->getCreatedTime(), REQUEST_TIME, 'Creating a user sets default "created" timestamp.');
    $this->assertEqual($user->getChangedTime(), REQUEST_TIME, 'Creating a user sets default "changed" timestamp.');

    // Create a field.
    $field_name = 'test_field';
    entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'user',
      'module' => 'image',
      'type' => 'image',
      'cardinality' => 1,
      'locked' => FALSE,
      'indexes' => array('target_id' => array('target_id')),
      'settings' => array(
        'uri_scheme' => 'public',
      ),
    ))->save();

    entity_create('field_config', array(
      'field_name' => $field_name,
      'entity_type' => 'user',
      'label' => 'Picture',
      'bundle' => 'user',
      'description' => t('Your virtual face or picture.'),
      'required' => FALSE,
      'settings' => array(
        'file_extensions' => 'png gif jpg jpeg',
        'file_directory' => 'pictures',
        'max_filesize' => '30 KB',
        'alt_field' => 0,
        'title_field' => 0,
        'max_resolution' => '85x85',
        'min_resolution' => '',
      ),
    ))->save();

    // Test user creation page for valid fields.
    $this->drupalGet('admin/people/create');
    $this->assertFieldbyId('edit-status-0', 0, 'The user status option Blocked exists.', 'User login');
    $this->assertFieldbyId('edit-status-1', 1, 'The user status option Active exists.', 'User login');
    $this->assertFieldByXPath('//input[@type="radio" and @id="edit-status-1" and @checked="checked"]', NULL, 'Default setting for user status is active.');

    // Test that browser autocomplete behavior does not occur.
    $this->assertNoRaw('data-user-info-from-browser', 'Ensure form attribute, data-user-info-from-browser, does not exist.');

    // Test that the password strength indicator displays.
    $config = $this->config('user.settings');

    $config->set('password_strength', TRUE)->save();
    $this->drupalGet('admin/people/create');
    $this->assertRaw(t('Password strength:'), 'The password strength indicator is displayed.');

    $config->set('password_strength', FALSE)->save();
    $this->drupalGet('admin/people/create');
    $this->assertNoRaw(t('Password strength:'), 'The password strength indicator is not displayed.');

    // We create two users, notifying one and not notifying the other, to
    // ensure that the tests work in both cases.
    foreach (array(FALSE, TRUE) as $notify) {
      $name = $this->randomMachineName();
      $edit = array(
        'name' => $name,
        'mail' => $this->randomMachineName() . '@example.com',
        'pass[pass1]' => $pass = $this->randomString(),
        'pass[pass2]' => $pass,
        'notify' => $notify,
      );
      $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));

      if ($notify) {
        $this->assertText(t('A welcome message with further instructions has been emailed to the new user @name.', array('@name' => $edit['name'])), 'User created');
        $this->assertEqual(count($this->drupalGetMails()), 1, 'Notification email sent');
      }
      else {
        $this->assertText(t('Created a new user account for @name. No email has been sent.', array('@name' => $edit['name'])), 'User created');
        $this->assertEqual(count($this->drupalGetMails()), 0, 'Notification email not sent');
      }

      $this->drupalGet('admin/people');
      $this->assertText($edit['name'], 'User found in list of users');
      $user = user_load_by_name($name);
      $this->assertEqual($user->isActive(), 'User is not blocked');
    }

    // Test that the password '0' is considered a password.
    // @see https://www.drupal.org/node/2563751.
    $name = $this->randomMachineName();
    $edit = array(
      'name' => $name,
      'mail' => $this->randomMachineName() . '@example.com',
      'pass[pass1]' => 0,
      'pass[pass2]' => 0,
      'notify' => FALSE,
    );
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));
    $this->assertText("Created a new user account for $name. No email has been sent");
    $this->assertNoText('Password field is required');
  }
}
