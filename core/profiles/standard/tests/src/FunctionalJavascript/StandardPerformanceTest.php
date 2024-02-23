<?php

declare(strict_types=1);

namespace Drupal\Tests\standard\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use Drupal\Tests\PerformanceData;
use Drupal\node\NodeInterface;

/**
 * Tests the performance of basic functionality in the standard profile.
 *
 * Stark is used as the default theme so that this test is not Olivero specific.
 *
 * @group Common
 */
class StandardPerformanceTest extends PerformanceTestBase {

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
   * Tests performance for anonymous users.
   */
  public function testAnonymous() {
    // Create two nodes to be shown on the front page.
    $this->drupalCreateNode([
      'type' => 'article',
      'promote' => NodeInterface::PROMOTED,
    ]);
    // Request a page that we're not otherwise explicitly testing to warm some
    // caches.
    $this->drupalGet('search');

    // Test frontpage.
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('');
    }, 'standardFrontPage');
    $this->assertNoJavaScript($performance_data);
    $this->assertCountBetween(33, 35, $performance_data->getQueryCount());
    $this->assertSame(137, $performance_data->getCacheGetCount());
    $this->assertSame(47, $performance_data->getCacheSetCount());
    $this->assertSame(0, $performance_data->getCacheDeleteCount());
    $this->assertCountBetween(40, 43, $performance_data->getCacheTagChecksumCount());
    $this->assertCountBetween(47, 50, $performance_data->getCacheTagIsValidCount());
    $this->assertSame(0, $performance_data->getCacheTagInvalidationCount());

    // Test node page.
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    });
    $this->assertNoJavaScript($performance_data);

    $this->assertSame(13, $performance_data->getQueryCount());
    $this->assertSame(95, $performance_data->getCacheGetCount());
    $this->assertSame(16, $performance_data->getCacheSetCount());
    $this->assertSame(0, $performance_data->getCacheDeleteCount());
    $this->assertCountBetween(24, 25, $performance_data->getCacheTagChecksumCount());
    $this->assertCountBetween(41, 42, $performance_data->getCacheTagIsValidCount());
    $this->assertSame(0, $performance_data->getCacheTagInvalidationCount());

    // Test user profile page.
    $user = $this->drupalCreateUser();
    $performance_data = $this->collectPerformanceData(function () use ($user) {
      $this->drupalGet('user/' . $user->id());
    });
    $this->assertNoJavaScript($performance_data);
    $this->assertSame(17, $performance_data->getQueryCount());
    $this->assertSame(81, $performance_data->getCacheGetCount());
    $this->assertSame(16, $performance_data->getCacheSetCount());
    $this->assertSame(0, $performance_data->getCacheDeleteCount());
    $this->assertCountBetween(24, 25, $performance_data->getCacheTagChecksumCount());
    $this->assertCountBetween(36, 37, $performance_data->getCacheTagIsValidCount());
    $this->assertSame(0, $performance_data->getCacheTagInvalidationCount());
  }

  /**
   * Tests the performance of logging in.
   */
  public function testLogin(): void {
    // Create a user and log them in to warm all caches. Manually submit the
    // form so that we repeat the same steps when recording performance data. Do
    // this twice so that any caches which take two requests to warm are also
    // covered.
    $account = $this->drupalCreateUser();
    foreach (range(0, 1) as $index) {
      $this->drupalGet('node');
      $this->drupalGet('user/login');
      $this->submitLoginForm($account);
      $this->drupalLogout();
    }

    $this->drupalGet('node');
    $this->drupalGet('user/login');
    $performance_data = $this->collectPerformanceData(function () use ($account) {
      $this->submitLoginForm($account);
    });

    $this->assertCountBetween(25, 30, $performance_data->getQueryCount());
    $this->assertSame(64, $performance_data->getCacheGetCount());
    $this->assertSame(1, $performance_data->getCacheSetCount());
    $this->assertSame(1, $performance_data->getCacheDeleteCount());
    $this->assertSame(1, $performance_data->getCacheTagChecksumCount());
    $this->assertSame(28, $performance_data->getCacheTagIsValidCount());
    $this->assertSame(0, $performance_data->getCacheTagInvalidationCount());
  }

  /**
   * Tests the performance of logging in via the user login block.
   */
  public function testLoginBlock(): void {
    $this->drupalPlaceBlock('user_login_block');
    // Create a user and log them in to warm all caches. Manually submit the
    // form so that we repeat the same steps when recording performance data. Do
    // this twice so that any caches which take two requests to warm are also
    // covered.
    $account = $this->drupalCreateUser();
    $this->drupalLogout();

    foreach (range(0, 1) as $index) {
      $this->drupalGet('node');
      $this->assertSession()->responseContains('Password');
      $this->submitLoginForm($account);
      $this->drupalLogout();
    }

    $this->drupalGet('node');
    $this->assertSession()->responseContains('Password');
    $performance_data = $this->collectPerformanceData(function () use ($account) {
      $this->submitLoginForm($account);
    });
    $this->assertCountBetween(30, 33, $performance_data->getQueryCount());
    $this->assertSame(85, $performance_data->getCacheGetCount());
    $this->assertSame(1, $performance_data->getCacheSetCount());
    $this->assertSame(1, $performance_data->getCacheDeleteCount());
    $this->assertSame(1, $performance_data->getCacheTagChecksumCount());
    $this->assertSame(31, $performance_data->getCacheTagIsValidCount());
    $this->assertSame(0, $performance_data->getCacheTagInvalidationCount());
  }

  /**
   * Submit the user login form.
   */
  protected function submitLoginForm($account) {
    $this->submitForm([
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
    ], 'Log in');
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

  /**
   * Provides an empty implementation to prevent the resetting of caches.
   */
  protected function refreshVariables() {}

}
