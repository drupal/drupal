<?php

declare(strict_types=1);

namespace Drupal\Tests\standard\FunctionalJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use Drupal\Tests\PerformanceData;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cSpell:ignore mlid
/**
 * Tests the performance of basic functionality in the standard profile.
 *
 * Stark is used as the default theme so that this test is not Olivero specific.
 */
#[Group('Common')]
#[Group('#slow')]
#[RequiresPhpExtension('apcu')]
#[RunTestsInSeparateProcesses]
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
   * The user account created during testing.
   */
  protected ?UserInterface $user = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create a content type and node to test performance for.
    $this->drupalCreateContentType(['type' => 'test_content', 'name' => 'Test Content']);
    $this->drupalCreateNode(['type' => 'test_content']);
    // Grant the anonymous user the permission to look at user profiles.
    user_role_grant_permissions('anonymous', ['access user profiles']);
  }

  /**
   * Tests performance of the standard profile.
   */
  public function testStandardPerformance(): void {
    $this->testAnonymous();
    $this->testLogin();
    $this->testLoginBlock();
    $this->testAdmin();
  }

  /**
   * Tests performance for anonymous users.
   */
  protected function testAnonymous(): void {
    // Request the front page, then immediately clear all object caches, so that
    // aggregates and image styles are created on disk but otherwise caches are
    // empty.
    $this->drupalGet('');
    // Give time for big pipe placeholders, asset aggregate requests, and post
    // response tasks to finish processing and write to any caches before
    // clearing caches again.
    sleep(2);
    foreach (Cache::getBins() as $bin) {
      $bin->deleteAll();
    }
    // Now visit a different page to warm some caches.
    $this->drupalGet('user/password');
    // Ensure everything finishes before we collect performance data.
    sleep(2);

    // Test frontpage.
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('');
    }, 'standardFrontPage');
    $this->assertNoJavaScript($performance_data);
    $this->assertQueriesByName('standardFrontPage', $performance_data->getQueries());
    $this->assertMetricsByName('standardFrontPage', $performance_data);

    // Test node page.
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'standardNodePage');

    $this->assertQueriesByName('standardNodePage', $performance_data->getQueries());
    $this->assertMetricsByName('standardNodePage', $performance_data);

    // Test user profile page.
    $this->user = $this->drupalCreateUser();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('user/' . $this->user->id());
    }, 'standardUserPage');
    $this->assertNoJavaScript($performance_data);
    $this->assertQueriesByName('standardUserPage', $performance_data->getQueries());
    $this->assertMetricsByName('standardUserPage', $performance_data);
  }

  /**
   * Tests the performance of logging in.
   */
  protected function testLogin(): void {
    // Create a user and log them in to warm all caches. Manually submit the
    // form so that we repeat the same steps when recording performance data. Do
    // this twice so that any caches which take two requests to warm are also
    // covered.
    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet('');
      $this->submitLoginForm($this->user);
      $this->drupalLogout();
    }

    $this->drupalGet('');
    $performance_data = $this->collectPerformanceData(function () {
      $this->submitLoginForm($this->user);
    }, 'standardLogin');

    $this->assertQueriesByName('standardLogin', $performance_data->getQueries());
    $this->assertMetricsByName('standardLogin', $performance_data);
    $this->drupalLogout();
  }

  /**
   * Tests the performance of logging in via the user login block.
   */
  protected function testLoginBlock(): void {
    $this->drupalPlaceBlock('user_login_block');
    // Log the user in in to warm all caches. Manually submit the form so that
    // we repeat the same steps when recording performance data. Do this twice
    // so that any caches which take two requests to warm are also covered.

    for ($i = 0; $i < 2; $i++) {
      $this->drupalGet('node/1');
      $this->assertSession()->responseContains('Password');
      $this->submitLoginForm($this->user);
      $this->drupalLogout();
    }

    $this->drupalGet('node/1');
    $this->assertSession()->responseContains('Password');
    $performance_data = $this->collectPerformanceData(function () {
      $this->submitLoginForm($this->user);
    }, 'standardBlockLogin');

    $this->assertQueriesByName('standardBlockLogin', $performance_data->getQueries());
    $this->assertMetricsByName('standardBlockLogin', $performance_data);
  }

  /**
   * Tests performance of a logged-in admin user with the navigation toolbar.
   */
  protected function testAdmin(): void {
    $admin_user = $this->drupalCreateUser();
    $admin_user->addRole('administrator');
    $admin_user->save();

    // Ensure no user is logged in and clear the render cache bin before
    // starting the warm-up, since prior sub-tests may leave an active session
    // and stale render cache entries.
    $this->drupalLogout();
    \Drupal::cache('render')->deleteAll();

    $this->drupalLogin($admin_user);
    // Request the node/1 page twice to ensure all cache collectors are fully
    // warmed. The exact contents of cache collectors depends on the order in
    // which requests complete so this ensures that the second request completes
    // after asset aggregates are served.
    $this->drupalGet('node/1');
    sleep(1);
    $this->drupalGet('node/1');
    // Flush the dynamic page cache to simulate visiting a page that is not
    // already fully cached.
    \Drupal::cache('dynamic_page_cache')->deleteAll();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'testAdmin');

    $this->assertQueriesByName('testAdmin', $performance_data->getQueries());
    $this->assertMetricsByName('testAdmin', $performance_data);

    // The navigation toolbar must be cached under low-cardinality contexts,
    // not per-user, to ensure it scales for authenticated admins.
    $this->assertIsObject(\Drupal::cache('render')->get('navigation:navigation:[languages:language_interface]=en:[theme]=stark:[user.permissions]=is-admin'));
  }

  /**
   * Submit the user login form.
   */
  protected function submitLoginForm($account): void {
    $this->submitForm([
      'name' => $account->getAccountName(),
      'pass' => $account->passRaw,
    ], 'Log in');
  }

  /**
   * Passes if no JavaScript is found on the page.
   *
   * @param \Drupal\Tests\PerformanceData $performance_data
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
