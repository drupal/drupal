<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Common\NoJavaScriptAnonymousTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\WebTestBase;

/**
 * Tests that anonymous users are not served any JavaScript.
 */
class NoJavaScriptAnonymousTest extends WebTestBase {

  protected $profile = 'standard';

  public static function getInfo() {
    return array(
      'name' => 'No JavaScript for anonymous users in Standard profile',
      'description' => 'Tests that anonymous users are not served any JavaScript in the Standard installation profile.',
      'group' => 'Common',
    );
  }

  protected function setUp() {
    parent::setUp();

    // Grant the anonymous user the permission to look at user profiles.
    user_role_grant_permissions('anonymous', array('access user profiles'));
  }

  /**
   * Tests that anonymous users are not served any JavaScript.
   */
  public function testNoJavaScript() {
    // Create a node that is listed on the frontpage.
    $this->drupalCreateNode(array(
      'promote' => NODE_PROMOTED,
    ));
    $user = $this->drupalCreateUser();

    // Test frontpage.
    $this->drupalGet('');
    $this->assertNoJavaScriptExceptHtml5Shiv();

    // Test node page.
    $this->drupalGet('node/1');
    $this->assertNoJavaScriptExceptHtml5Shiv();

    // Test user profile page.
    $this->drupalGet('user/' . $user->id());
    $this->assertNoJavaScriptExceptHtml5Shiv();
  }

  /**
   * Passes if no JavaScript is found on the page except the HTML5 shiv.
   *
   * The HTML5 shiv is necessary for e.g. the <article> tag which Drupal 8 uses
   * to work in older browsers like Internet Explorer 8.
   */
  protected function assertNoJavaScriptExceptHtml5Shiv() {
    // Ensure drupalSettings is not set.
    $this->assertNoRaw('var drupalSettings = {', 'drupalSettings is not set.');

    // Ensure the HTML5 shiv exists.
    $this->assertRaw('html5shiv/html5.js', 'HTML5 shiv JavaScript exists.');

    // Ensure no other JavaScript file exists on the page, while ignoring the
    // HTML5 shiv.
    $this->assertNoPattern('/(?<!html5)\.js/', "No other JavaScript exists.");
  }

}
