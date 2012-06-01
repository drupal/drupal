<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserAccountLinksTests.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test user-links in secondary menu.
 */
class UserAccountLinksTests extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User account links',
      'description' => 'Test user-account links.',
      'group' => 'User'
    );
  }

  /**
   * Tests the secondary menu.
   */
  function testSecondaryMenu() {
    // Create a regular user.
    $user = $this->drupalCreateUser(array());

    // Log in and get the homepage.
    $this->drupalLogin($user);
    $this->drupalGet('<front>');

    // For a logged-in user, expect the secondary menu to have links for "My
    // account" and "Log out".
    $link = $this->xpath('//ul[@id=:menu_id]/li/a[contains(@href, :href) and text()=:text]', array(
      ':menu_id' => 'secondary-menu',
      ':href' => 'user',
      ':text' => 'My account',
    ));
    $this->assertEqual(count($link), 1, 'My account link is in secondary menu.');

    $link = $this->xpath('//ul[@id=:menu_id]/li/a[contains(@href, :href) and text()=:text]', array(
      ':menu_id' => 'secondary-menu',
      ':href' => 'user/logout',
      ':text' => 'Log out',
    ));
    $this->assertEqual(count($link), 1, 'Log out link is in secondary menu.');

    // Log out and get the homepage.
    $this->drupalLogout();
    $this->drupalGet('<front>');

    // For a logged-out user, expect no secondary links.
    $element = $this->xpath('//ul[@id=:menu_id]', array(':menu_id' => 'secondary-menu'));
    $this->assertEqual(count($element), 0, 'No secondary-menu for logged-out users.');
  }
}
