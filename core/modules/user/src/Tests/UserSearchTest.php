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
    // Verify that a user without 'administer users' permission cannot search
    // for users by email address.
    $user1 = $this->drupalCreateUser(array('access user profiles', 'search content'));
    $this->drupalLogin($user1);
    $keys = $user1->getEmail();
    $edit = array('keys' => $keys);
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertNoText($keys, 'Search by email did not work for non-admin user');
    $this->assertText('no results', 'Search by email gave no-match message');

    // Verify that a non-matching query gives an appropriate message.
    $keys = 'nomatch';
    $edit = array('keys' => $keys);
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertText('no results', 'Non-matching search gave appropriate message');

    // Verify that a user with search permission can search for users by name.
    $keys = $user1->getUsername();
    $edit = array('keys' => $keys);
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertLink($keys, 0, 'Search by user name worked for non-admin user');

    // Verify that searching by sub-string works too.
    $subkey = substr($keys, 1, 5);
    $edit = array('keys' => $subkey);
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertLink($keys, 0, 'Search by user name substring worked for non-admin user');

    // Verify that a user with 'administer users' permission can search by
    // email.
    $user2 = $this->drupalCreateUser(array('administer users', 'access user profiles', 'search content'));
    $this->drupalLogin($user2);
    $keys = $user2->getEmail();
    $edit = array('keys' => $keys);
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertText($keys, 'Search by email works for administrative user');
    $this->assertText($user2->getUsername(), 'Search by email resulted in user name on page for administrative user');

    // Verify that a substring works too for email.
    $subkey = substr($keys, 1, 5);
    $edit = array('keys' => $subkey);
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertText($keys, 'Search by email substring works for administrative user');
    $this->assertText($user2->getUsername(), 'Search by email substring resulted in user name on page for administrative user');

    // Verify that if they search by user name, they see email address too.
    $keys = $user1->getUsername();
    $edit = array('keys' => $keys);
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertText($keys, 'Search by user name works for admin user');
    $this->assertText($user1->getEmail(), 'Search by user name for admin shows email address too');

    // Create a blocked user.
    $blocked_user = $this->drupalCreateUser();
    $blocked_user->block();
    $blocked_user->save();

    // Verify that users with "administer users" permissions can see blocked
    // accounts in search results.
    $edit = array('keys' => $blocked_user->getUsername());
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertText($blocked_user->getUsername(), 'Blocked users are listed on the user search results for users with the "administer users" permission.');

    // Verify that users without "administer users" permissions do not see
    // blocked accounts in search results.
    $this->drupalLogin($user1);
    $edit = array('keys' => $blocked_user->getUsername());
    $this->drupalPostForm('search/user', $edit, t('Search'));
    $this->assertNoText($blocked_user->getUsername(), 'Blocked users are hidden from the user search results.');

    // Create a user without search permission, and one without user page view
    // permission. Verify that neither one can access the user search page.
    $user3 = $this->drupalCreateUser(array('search content'));
    $this->drupalLogin($user3);
    $this->drupalGet('search/user');
    $this->assertResponse('403', 'User without user profile access cannot search');

    $user4 = $this->drupalCreateUser(array('access user profiles'));
    $this->drupalLogin($user4);
    $this->drupalGet('search/user');
    $this->assertResponse('403', 'User without search permission cannot search');
    $this->drupalLogout();
  }
}
