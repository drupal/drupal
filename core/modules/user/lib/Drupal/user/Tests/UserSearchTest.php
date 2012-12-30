<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserSearchTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test user search.
 */
class UserSearchTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('search');

  public static function getInfo() {
    return array(
      'name' => 'User search',
      'description' => 'Tests the user search page and verifies that sensitive information is hidden from unauthorized users.',
      'group' => 'User',
    );
  }

  function testUserSearch() {
    $user1 = $this->drupalCreateUser(array('access user profiles', 'search content', 'use advanced search'));
    $this->drupalLogin($user1);
    $keys = $user1->mail;
    $edit = array('keys' => $keys);
    $this->drupalPost('search/user/', $edit, t('Search'));
    $this->assertNoText($keys);
    $this->drupalLogout();

    $user2 = $this->drupalCreateUser(array('administer users', 'access user profiles', 'search content', 'use advanced search'));
    $this->drupalLogin($user2);
    $keys = $user2->mail;
    $edit = array('keys' => $keys);
    $this->drupalPost('search/user/', $edit, t('Search'));
    $this->assertText($keys);

    // Create a blocked user.
    $blocked_user = $this->drupalCreateUser();
    $blocked_user->status = 0;
    $blocked_user->save();

    // Verify that users with "administer users" permissions can see blocked
    // accounts in search results.
    $edit = array('keys' => $blocked_user->name);
    $this->drupalPost('search/user/', $edit, t('Search'));
    $this->assertText($blocked_user->name, 'Blocked users are listed on the user search results for users with the "administer users" permission.');

    // Verify that users without "administer users" permissions do not see
    // blocked accounts in search results.
    $this->drupalLogin($user1);
    $edit = array('keys' => $blocked_user->name);
    $this->drupalPost('search/user/', $edit, t('Search'));
    $this->assertNoText($blocked_user->name, 'Blocked users are hidden from the user search results.');

    $this->drupalLogout();
  }
}
