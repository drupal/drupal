<?php

namespace Drupal\Tests\standard\FunctionalJavascript;

use Drupal\Tests\PerformanceData;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use Drupal\node\NodeInterface;

/**
 * Tests that anonymous users are not served any JavaScript.
 *
 * This is tested with the core modules that are enabled in the 'standard'
 * profile.
 *
 * @group Common
 */
class NoJavaScriptAnonymousTest extends PerformanceTestBase {

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
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('');
    });
    $this->assertNoJavaScript($performance_data);

    // Test node page.
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    });
    $this->assertNoJavaScript($performance_data);

    // Test user profile page.
    $user = $this->drupalCreateUser();
    $performance_data = $this->collectPerformanceData(function () use ($user) {
      $this->drupalGet('user/' . $user->id());
    });
    $this->assertNoJavaScript($performance_data);
  }

  /**
   * Passes if no JavaScript is found on the page.
   *
   * @param Drupal\Tests\PerformanceData $performance_data
   *   A PerformanceData value object.
   *
   * @internal
   */
  protected function assertNoJavaScript(PerformanceData $performance_data): void {
    // Ensure drupalSettings is not set.
    $settings = $this->getDrupalSettings();
    $this->assertEmpty($settings, 'drupalSettings is not set.');
    $this->assertSession()->responseNotMatches('/\.js/');
    $this->assertSame(0, $performance_data->getScriptCount());
  }

}
