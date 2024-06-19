<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\PerformanceTestBase;

/**
 * Tests demo_umami profile performance.
 *
 * @group OpenTelemetry
 * @group #slow
 * @requires extension apcu
 */
class OpenTelemetryNodePagePerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * Logs node page tracing data with a cold cache.
   */
  public function testNodePageColdCache(): void {
    // @todo Chromedriver doesn't collect tracing performance logs for the very
    //   first request in a test, so warm it up.
    //   https://www.drupal.org/project/drupal/issues/3379750
    $this->drupalGet('user/login');
    $this->rebuildAll();
    $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageColdCache');
    $this->assertSession()->pageTextContains('quiche');
  }

  /**
   * Logs node page tracing data with a hot cache.
   *
   * Hot here means that all possible caches are warmed.
   */
  public function testNodePageHotCache(): void {
    // Request the page twice so that asset aggregates are definitely cached in
    // the browser cache.
    $this->drupalGet('node/1');
    $this->drupalGet('node/1');

    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageHotCache');
    $this->assertSession()->pageTextContains('quiche');
    $this->assertSame($performance_data->getQueryCount(), 0);
    $this->assertSame($performance_data->getCacheGetCount(), 1);
    $this->assertSame($performance_data->getCacheSetCount(), 0);
    $this->assertSame($performance_data->getCacheDeleteCount(), 0);
    $this->assertSame(0, $performance_data->getCacheTagChecksumCount());
    $this->assertSame(1, $performance_data->getCacheTagIsValidCount());
  }

  /**
   * Logs node/1 tracing data with a cool cache.
   *
   * Cool here means that 'global' site caches are warm but anything
   * specific to the route or path is cold.
   */
  public function testNodePageCoolCache(): void {
    // First of all visit the node page to ensure the image style exists.
    $this->drupalGet('node/1');
    $this->rebuildAll();
    // Now visit a non-node page to warm non-route-specific caches.
    $this->drupalGet('user/login');
    $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageCoolCache');
    $this->assertSession()->pageTextContains('quiche');
  }

  /**
   * Log node/1 tracing data with a warm cache.
   *
   * Warm here means that 'global' site caches and route-specific caches are
   * warm but caches specific to this particular node/path are not.
   */
  public function testNodePageWarmCache(): void {
    // First of all visit the node page to ensure the image style exists.
    $this->drupalGet('node/1');
    $this->rebuildAll();
    // Now visit a different node page to warm non-path-specific caches.
    $this->drupalGet('node/2');
    $this->collectPerformanceData(function () {
      $this->drupalGet('node/1');
    }, 'umamiNodePageWarmCache');
    $this->assertSession()->pageTextContains('quiche');
  }

}
