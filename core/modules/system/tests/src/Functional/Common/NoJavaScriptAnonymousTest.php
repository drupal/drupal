<?php

namespace Drupal\Tests\system\Functional\Common;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that anonymous users are not served any JavaScript in the Standard
 * installation profile.
 *
 * @group Common
 */
class NoJavaScriptAnonymousTest extends BrowserTestBase {

  protected $profile = 'standard';

  protected function setUp() {
    parent::setUp();

    // Grant the anonymous user the permission to look at user profiles.
    user_role_grant_permissions('anonymous', ['access user profiles']);
  }

  /**
   * Tests that anonymous users are not served any JavaScript.
   */
  public function testNoJavaScript() {
    // Create a node that is listed on the frontpage.
    $this->drupalCreateNode([
      'promote' => NodeInterface::PROMOTED,
    ]);
    $user = $this->drupalCreateUser();

    // Test frontpage.
    $this->drupalGet('');
    $this->assertNoJavaScript();

    // Test node page.
    $this->drupalGet('node/1');
    $this->assertNoJavaScript();

    // Test user profile page.
    $this->drupalGet('user/' . $user->id());
    $this->assertNoJavaScript();
  }

  /**
   * Passes if no JavaScript is found on the page.
   */
  protected function assertNoJavaScript() {
    // Ensure drupalSettings is not set.
    $settings = $this->getDrupalSettings();
    $this->assertTrue(empty($settings), 'drupalSettings is not set.');
    $this->assertSession()->responseNotMatches('/\.js/');
  }

}
