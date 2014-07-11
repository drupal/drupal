<?php

/**
 * @file
 * Contains Drupal\minimal\Tests\MinimalTest.
 */

namespace Drupal\minimal\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Minimal installation profile expectations.
 *
 * @group minimal
 */
class MinimalTest extends WebTestBase {

  protected $profile = 'minimal';

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
