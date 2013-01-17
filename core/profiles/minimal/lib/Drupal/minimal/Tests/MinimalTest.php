<?php

/**
 * @file
 * Contains Drupal\minimal\Tests\MinimalTest.
 */

namespace Drupal\minimal\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Minimal installation profile expectations.
 */
class MinimalTest extends WebTestBase {

  protected $profile = 'minimal';

  public static function getInfo() {
    return array(
      'name' => 'Minimal installation profile',
      'description' => 'Tests Minimal installation profile expectations.',
      'group' => 'Minimal',
    );
  }

  /**
   * Tests Minimal installation profile.
   */
  function testMinimal() {
    $this->drupalGet('');
    // Check the login block is present.
    $this->assertLink(t('Create new account'));
    $this->assertResponse(200);

    // Create a user to test tools and navigation blocks for logged in users
    // with appropriate permissions.
    $user = $this->drupalCreateUser(array('access administration pages', 'administer content types'));
    $this->drupalLogin($user);
    $this->drupalGet('');
    $this->assertText(t('Tools'));
    $this->assertText(t('Administration'));
  }
}
