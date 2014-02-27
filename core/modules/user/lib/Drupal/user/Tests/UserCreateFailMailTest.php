<?php

/**
 * @file
 * Contains Drupal\user\Tests\UserCreateFailMailTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the create user administration page.
 */
class UserCreateFailMailTest extends WebTestBase {

  /**
   * Modules to enable
   *
   * @var array
   */
  public static $modules = array('system_mail_failure_test');

  public static function getInfo() {
    return array(
      'name' => 'User create with failed mail function',
      'description' => 'Test the create user administration page.',
      'group' => 'User',
    );
  }

  /**
   * Tests the create user administration page.
   */
  protected function testUserAdd() {
    $user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($user);

    // Replace the mail functionality with a fake, malfunctioning service.
    \Drupal::config('system.mail')->set('interface.default', 'test_php_mail_failure')->save();
    // Create a user, but fail to send an email.
    $name = $this->randomName();
    $edit = array(
      'name' => $name,
      'mail' => $this->randomName() . '@example.com',
      'pass[pass1]' => $pass = $this->randomString(),
      'pass[pass2]' => $pass,
      'notify' => TRUE,
    );
    $this->drupalPostForm('admin/people/create', $edit, t('Create new account'));

    $this->assertText(t('Unable to send e-mail. Contact the site administrator if the problem persists.'));
    $this->assertNoText(t('A welcome message with further instructions has been e-mailed to the new user @name.', array('@name' => $edit['name'])));
  }
}
