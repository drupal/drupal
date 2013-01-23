<?php

/**
 * @file
 * Contains \Drupal\simpletest\Tests\UserLoginTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests User related helper methods of WebTestBase.
 */
class UserHelpersTest extends WebTestBase {

  public static function getInfo() {
    return array(
      'name' => 'User helper methods',
      'description' => 'Tests User related helper methods of WebTestBase.',
      'group' => 'SimpleTest',
    );
  }

  /**
   * Tests WebTestBase::drupalUserIsLoggedIn().
   */
  function testDrupalUserIsLoggedIn() {
    $first_user = $this->drupalCreateUser();
    $second_user = $this->drupalCreateUser();

    // After logging in, the first user should be logged in, the second not.
    $this->drupalLogin($first_user);
    $this->assertTrue($this->drupalUserIsLoggedIn($first_user));
    $this->assertFalse($this->drupalUserIsLoggedIn($second_user));

    // Verify that logged in state is retained across pages.
    $this->drupalGet('');
    $this->assertTrue($this->drupalUserIsLoggedIn($first_user));
    $this->assertFalse($this->drupalUserIsLoggedIn($second_user));

    // After logging out, both users should be logged out.
    $this->drupalLogout();
    $this->assertFalse($this->drupalUserIsLoggedIn($first_user));
    $this->assertFalse($this->drupalUserIsLoggedIn($second_user));

    // After logging back in, the second user should still be logged out.
    $this->drupalLogin($first_user);
    $this->assertTrue($this->drupalUserIsLoggedIn($first_user));
    $this->assertFalse($this->drupalUserIsLoggedIn($second_user));

    // After logging in the second user, the first one should be logged out.
    $this->drupalLogin($second_user);
    $this->assertTrue($this->drupalUserIsLoggedIn($second_user));
    $this->assertFalse($this->drupalUserIsLoggedIn($first_user));

    // After logging out, both should be logged out.
    $this->drupalLogout();
    $this->assertFalse($this->drupalUserIsLoggedIn($first_user));
    $this->assertFalse($this->drupalUserIsLoggedIn($second_user));
  }

}
