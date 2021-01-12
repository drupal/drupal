<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the user search page and verifies that sensitive information is hidden
 * from unauthorized users.
 *
 * @group user
 */
class UserSearchTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['search'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testUserSearch() {
    // Verify that a user without 'administer users' permission cannot search
    // for users by email address. Additionally, ensure that the username has a
    // plus sign to ensure searching works with that.
    $user1 = $this->drupalCreateUser([
      'access user profiles',
      'search content',
    ], "foo+bar");
    $this->drupalLogin($user1);
    $keys = $user1->getEmail();
    $edit = ['keys' => $keys];
    $this->drupalPostForm('search/user', $edit, 'Search');
    $this->assertText('Your search yielded no results.', 'Search by email did not work for non-admin user');
    $this->assertText('no results', 'Search by email gave no-match message');

    // Verify that a non-matching query gives an appropriate message.
    $keys = 'nomatch';
    $edit = ['keys' => $keys];
    $this->drupalPostForm('search/user', $edit, 'Search');
    $this->assertText('no results', 'Non-matching search gave appropriate message');

    // Verify that a user with search permission can search for users by name.
    $keys = $user1->getAccountName();
    $edit = ['keys' => $keys];
    $this->drupalPostForm('search/user', $edit, 'Search');
    $this->assertSession()->linkExists($keys, 0, 'Search by username worked for non-admin user');

    // Verify that searching by sub-string works too.
    $subkey = substr($keys, 1, 5);
    $edit = ['keys' => $subkey];
    $this->drupalPostForm('search/user', $edit, 'Search');
    $this->assertSession()->linkExists($keys, 0, 'Search by username substring worked for non-admin user');

    // Verify that wildcard search works.
    $subkey = substr($keys, 0, 2) . '*' . substr($keys, 4, 2);
    $edit = ['keys' => $subkey];
    $this->drupalPostForm('search/user', $edit, 'Search');
    $this->assertSession()->linkExists($keys, 0, 'Search with wildcard worked for non-admin user');

    // Verify that a user with 'administer users' permission can search by
    // email.
    $user2 = $this->drupalCreateUser([
      'administer users',
      'access user profiles',
      'search content',
    ]);
    $this->drupalLogin($user2);
    $keys = $user2->getEmail();
    $edit = ['keys' => $keys];
    $this->drupalPostForm('search/user', $edit, 'Search');
    $this->assertText($keys, 'Search by email works for administrative user');
    $this->assertText($user2->getAccountName(), 'Search by email resulted in username on page for administrative user');

    // Verify that a substring works too for email.
    $subkey = substr($keys, 1, 5);
    $edit = ['keys' => $subkey];
    $this->drupalPostForm('search/user', $edit, 'Search');
    $this->assertText($keys, 'Search by email substring works for administrative user');
    $this->assertText($user2->getAccountName(), 'Search by email substring resulted in username on page for administrative user');

    // Verify that wildcard search works for email
    $subkey = substr($keys, 0, 2) . '*' . substr($keys, 4, 2);
    $edit = ['keys' => $subkey];
    $this->drupalPostForm('search/user', $edit, 'Search');
    $this->assertText($user2->getAccountName(), 'Search for email wildcard resulted in username on page for administrative user');

    // Verify that if they search by user name, they see email address too.
    $keys = $user1->getAccountName();
    $edit = ['keys' => $keys];
    $this->drupalPostForm('search/user', $edit, 'Search');
    $this->assertText($keys, 'Search by username works for admin user');
    $this->assertText($user1->getEmail(), 'Search by username for admin shows email address too');

    // Create a blocked user.
    $blocked_user = $this->drupalCreateUser();
    $blocked_user->block();
    $blocked_user->save();

    // Verify that users with "administer users" permissions can see blocked
    // accounts in search results.
    $edit = ['keys' => $blocked_user->getAccountName()];
    $this->drupalPostForm('search/user', $edit, 'Search');
    $this->assertText($blocked_user->getAccountName(), 'Blocked users are listed on the user search results for users with the "administer users" permission.');

    // Verify that users without "administer users" permissions do not see
    // blocked accounts in search results.
    $this->drupalLogin($user1);
    $edit = ['keys' => $blocked_user->getAccountName()];
    $this->drupalPostForm('search/user', $edit, 'Search');
    $this->assertText('Your search yielded no results.', 'Blocked users are hidden from the user search results.');

    // Ensure that a user without access to user profiles cannot access the
    // user search page.
    $user3 = $this->drupalCreateUser(['search content']);
    $this->drupalLogin($user3);
    $this->drupalGet('search/user');
    $this->assertSession()->statusCodeEquals(403);

    // Ensure that a user without search permission cannot access the user
    // search page.
    $user4 = $this->drupalCreateUser(['access user profiles']);
    $this->drupalLogin($user4);
    $this->drupalGet('search/user');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout();
  }

}
