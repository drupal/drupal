<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore languageswitcher
/**
 * Tests demo_umami profile performance.
 */
#[Group('OpenTelemetry')]
#[Group('#slow')]
#[RequiresPhpExtension('apcu')]
#[RunTestsInSeparateProcesses]
class OpenTelemetryPerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * Test performance of various with various cache permutations.
   */
  public function testUmamiPerformance(): void {
    $this->testNodePageColdCache();
    $this->testNodePageCoolCache();
    $this->testNodePageWarmCache();
    $this->testNodePageHotCache();
    $this->testFrontPageColdCache();
    $this->testFrontPageCoolCache();
    $this->testFrontPageHotCache();
    $this->doTestFrontPageAuthenticatedWarmCache();
    $this->doTestNodePageAdministrator();
  }

  /**
   * Logs node page tracing data with a cold cache.
   */
  protected function testNodePageColdCache(): void {
    // Request the node page twice then clear caches, this allows asset
    // aggregate requests to complete so they are excluded from the performance
    // test itself. Including the asset aggregates would lead to
    // a non-deterministic test since they happen in parallel and therefore post
    // response tasks run in different orders each time.
    $this->drupalGet('node/1');
    // Allow time for image style and aggregate requests to finish.
    sleep(2);
    $this->drupalGet('node/1');
    $this->clearCaches();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageColdCache');
    $this->assertSession()->pageTextContains('quiche');

    $this->assertMetricsByName('umamiNodePageColdCache', $performance_data);
  }

  /**
   * Logs node page tracing data with a hot cache.
   *
   * Hot here means that all possible caches are warmed.
   */
  protected function testNodePageHotCache(): void {
    // The node/1 asset aggregates and image styles have already been warmed by
    // ::testNodePageColdCache().
    $this->drupalGet('node/1');
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageHotCache');
    $this->assertSession()->pageTextContains('quiche');

    $this->assertMetricsByName('umamiNodePageHotCache', $performance_data);
  }

  /**
   * Logs node/1 tracing data with a cool cache.
   *
   * Cool here means that 'global' site caches are warm but anything
   * specific to the route or path is cold.
   */
  protected function testNodePageCoolCache(): void {
    // The node/1 asset aggregates and image styles have already been warmed by
    // ::testNodePageColdCache().
    $this->clearCaches();
    $this->drupalGet('user/login');
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageCoolCache');
    $this->assertSession()->pageTextContains('quiche');

    $this->assertMetricsByName('umamiNodePageCoolCache', $performance_data);
  }

  /**
   * Log node/1 tracing data with a warm cache.
   *
   * Warm here means that 'global' site caches and route-specific caches are
   * warm but caches specific to this particular node/path are not.
   */
  protected function testNodePageWarmCache(): void {
    // The node/1 asset aggregates and image styles have already been warmed by
    // ::testNodePageColdCache().
    $this->clearCaches();
    // Now visit a different node page to warm non-path-specific caches.
    $this->drupalGet('node/2');
    sleep(1);
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageWarmCache');
    $this->assertSession()->pageTextContains('quiche');
    // Check the actual queries so that if a change simultaneously adds and
    // removes a query the change is detected.
    $this->assertQueriesByName('umamiNodePageWarmCache', $performance_data->getQueries());
    $this->assertMetricsByName('umamiNodePageWarmCache', $performance_data);
  }

  /**
   * Logs front page tracing data with a cold cache.
   */
  protected function testFrontPageColdCache(): void {
    // Request the front page then clear caches, this allows asset aggregate
    // requests to complete so they are excluded from the performance test
    // itself. Including the asset aggregates would lead to a non-deterministic
    // test since they happen in parallel and therefore post response tasks run
    // in different orders each time.
    $this->drupalGet('<front>');
    sleep(2);
    $this->clearCaches();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'umamiFrontPageColdCache');
    $this->assertSession()->pageTextContains('Umami');

    $this->assertMetricsByName('umamiFrontPageColdCache', $performance_data);
  }

  /**
   * Logs front page tracing data with a hot cache.
   *
   * Hot here means that all possible caches are warmed.
   */
  protected function testFrontPageHotCache(): void {
    // The front page has already been warmed by ::testFrontPageCoolCache();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'umamiFrontPageHotCache');
    $this->assertSession()->pageTextContains('Umami');

    $this->assertMetricsByName('umamiFrontPageHotCache', $performance_data);
  }

  /**
   * Logs front page tracing data with a lukewarm cache.
   *
   * Cool here means that 'global' site caches are warm but anything
   * specific to the front page is cold.
   */
  protected function testFrontPageCoolCache(): void {
    // The front page has already been warmed by ::testFrontPageColdCache().
    $this->clearCaches();
    // Now visit a different page to warm non-route-specific caches.
    $this->drupalGet('user/login');
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'umamiFrontPageCoolCache');

    $this->assertMetricsByName('umamiFrontPageCoolCache', $performance_data);
  }

  /**
   * Logs front page tracing data with an authenticated user and warm cache.
   */
  protected function doTestFrontPageAuthenticatedWarmCache(): void {
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
    sleep(2);
    $this->drupalGet('<front>');
    sleep(2);
    $this->drupalGet('<front>');
    sleep(2);
    $this->drupalGet('<front>');
    sleep(2);

    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'authenticatedFrontPage');

    $this->assertQueriesByName('authenticatedFrontPage', $performance_data->getQueries());
    $this->assertMetricsByName('authenticatedFrontPage', $performance_data);
  }

  /**
   * Logs node page performance with an administrator.
   */
  protected function doTestNodePageAdministrator(): void {
    // Create a user with most important admin permissions, but not access to
    // contextual links. This is because contextual module makes an AJAX request
    // dependent on the content of browser local storage, which can make
    // performance testing indeterminate, use a deterministic user role.
    $this->createRole([
      'administer nodes',
      'bypass node access',
      'access administration pages',
      'administer site configuration',
      'administer modules',
      'administer themes',
      'access site reports',
      'administer users',
      'access navigation',
      'administer media',
      'access files overview',
      'administer blocks',
      'administer block content',
      'administer taxonomy',
      'administer menu',
    ], 'test_admin');
    $user = $this->drupalCreateUser(values: ['roles' => ['test_admin']]);

    $this->drupalLogin($user);

    // Ensure the asset cache warming request happens with empty caches,
    // otherwise the unique combination of assets for the performance request
    // may not have been created yet.
    $this->clearCaches();

    $this->drupalGet('node/1');
    sleep(2);
    $this->drupalGet('node/1');
    sleep(2);

    $this->clearCaches();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'administratorNodePage');

    $this->assertMetricsByName('administratorNodePage', $performance_data);
  }

  /**
   * Clear caches.
   */
  protected function clearCaches(): void {
    foreach (Cache::getBins() as $bin) {
      $bin->deleteAll();
    }
  }

}
