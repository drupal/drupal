<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserEditedOwnAccountTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests user login after editing own user account.
 */
class UserEditedOwnAccountTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User edited own account',
      'description' => 'Test user edited own account can still log in.',
      'group' => 'User',
    );
  }

  function testUserEditedOwnAccount() {
    // Change account setting 'Who can register accounts?' to Administrators
    // only.
    \Drupal::config('user.settings')->set('register', USER_REGISTER_ADMINISTRATORS_ONLY)->save();

    // Create a new user account and log in.
    $account = $this->drupalCreateUser(array('change own username'));
    $this->drupalLogin($account);

    // Change own username.
    $edit = array();
    $edit['name'] = $this->randomName();
    $this->drupalPostForm('user/' . $account->id() . '/edit', $edit, t('Save'));

    // Log out.
    $this->drupalLogout();

    // Set the new name on the user account and attempt to log back in.
    $account->name = $edit['name'];
    $this->drupalLogin($account);
  }
}
