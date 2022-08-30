<?php

namespace Drupal\Tests\system\Functional\Common;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that anonymous users are not served any JavaScript.
 *
 * This is tested with the core modules that are enabled in the 'standard'
 * profile.
 *
 * @group Common
 */
class NoJavaScriptAnonymousTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Grant the anonymous user the permission to look at user profiles.
    user_role_grant_permissions('anonymous', ['access user profiles']);
  }

  /**
   * Tests that anonymous users are not served any JavaScript.
   */
  public function testNoJavaScript() {
    // Create a node of content type 'article' that is listed on the frontpage.
    $this->drupalCreateNode([
      'type' => 'article',
      'promote' => NodeInterface::PROMOTED,
    ]);

    // Test frontpage.
    $this->drupalGet('');
    $this->assertNoJavaScript();

    // Test node page.
    $this->drupalGet('node/1');
    $this->assertNoJavaScript();

    // Test user profile page.
    $user = $this->drupalCreateUser();
    $this->drupalGet('user/' . $user->id());
    $this->assertNoJavaScript();
  }

  /**
   * Passes if no JavaScript is found on the page.
   *
   * @internal
   */
  protected function assertNoJavaScript(): void {
    // Ensure drupalSettings is not set.
    $settings = $this->getDrupalSettings();
    $this->assertEmpty($settings, 'drupalSettings is not set.');
    $this->assertSession()->responseNotMatches('/\.js/');
  }

}
