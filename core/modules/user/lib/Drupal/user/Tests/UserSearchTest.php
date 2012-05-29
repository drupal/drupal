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
  public static function getInfo() {
    return array(
      'name' => 'User search',
      'description' => 'Testing that only user with the right permission can see the email address in the user search.',
      'group' => 'User',
    );
  }

  function setUp() {
    parent::setUp(array('search'));
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
    $this->drupalLogout();
  }
}
