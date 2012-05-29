<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserCreateTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test the create user administration page.
 */
class UserCreateTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User create',
      'description' => 'Test the create user administration page.',
      'group' => 'User',
    );
  }

  /**
   * Create a user through the administration interface and ensure that it
   * displays in the user list.
   */
  protected function testUserAdd() {
    $user = $this->drupalCreateUser(array('administer users'));
    $this->drupalLogin($user);

    foreach (array(FALSE, TRUE) as $notify) {
      $edit = array(
        'name' => $this->randomName(),
        'mail' => $this->randomName() . '@example.com',
        'pass[pass1]' => $pass = $this->randomString(),
        'pass[pass2]' => $pass,
        'notify' => $notify,
      );
      $this->drupalPost('admin/people/create', $edit, t('Create new account'));

      if ($notify) {
        $this->assertText(t('A welcome message with further instructions has been e-mailed to the new user @name.', array('@name' => $edit['name'])), 'User created');
        $this->assertEqual(count($this->drupalGetMails()), 1, 'Notification e-mail sent');
      }
      else {
        $this->assertText(t('Created a new user account for @name. No e-mail has been sent.', array('@name' => $edit['name'])), 'User created');
        $this->assertEqual(count($this->drupalGetMails()), 0, 'Notification e-mail not sent');
      }

      $this->drupalGet('admin/people');
      $this->assertText($edit['name'], 'User found in list of users');
    }
  }
}
