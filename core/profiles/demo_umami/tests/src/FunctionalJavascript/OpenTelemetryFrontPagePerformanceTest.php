<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;

/**
 * Tests demo_umami profile performance.
 *
 * @group OpenTelemetry
 * @group #slow
 * @requires extension apcu
 */
class OpenTelemetryFrontPagePerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * Tests performance of the Umami demo front page.
   */
  public function testFrontPagePerformance(): void {
    $this->testFrontPageColdCache();
    $this->testFrontPageCoolCache();
    $this->testFrontPageHotCache();
  }

  /**
   * Logs front page tracing data with a cold cache.
   */
  protected function testFrontPageColdCache(): void {
    // Request the front page twice then clear caches, this allows asset
    // aggregate requests to complete so they are excluded from the performance
    // test itself. Including the asset aggregates would lead to
    // a non-deterministic test since they happen in parallel and therefore post
    // response tasks run in different orders each time.
    $this->drupalGet('<front>');
    $this->drupalGet('<front>');
    sleep(2);
    $this->clearCaches();
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'umamiFrontPageColdCache');
    $this->assertSession()->pageTextContains('Umami');

    $expected = [
      'QueryCount' => 381,
      'CacheGetCount' => 471,
      'CacheSetCount' => 467,
      'CacheDeleteCount' => 0,
      'CacheTagLookupQueryCount' => 49,
      'CacheTagInvalidationCount' => 0,
      'ScriptCount' => 1,
      'ScriptBytes' => 12000,
      'StylesheetCount' => 2,
      'StylesheetBytes' => 39750,
    ];
    $this->assertMetrics($expected, $performance_data);
  }

  /**
   * Logs front page tracing data with a hot cache.
   *
   * Hot here means that all possible caches are warmed.
   */
  protected function testFrontPageHotCache(): void {
    // Request the page twice so that asset aggregates and image derivatives are
    // definitely cached in the browser cache. The first response builds the
    // file and serves from PHP with private, no-store headers. The second
    // request will get the file served directly from disk by the browser with
    // cacheable headers, so only the third request actually has the files
    // in the browser cache.
    $this->drupalGet('<front>');
    $this->drupalGet('<front>');
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'umamiFrontPageHotCache');
    $this->assertSession()->pageTextContains('Umami');

    $expected_queries = [];
    $recorded_queries = $performance_data->getQueries();
    $this->assertSame($expected_queries, $recorded_queries);

    $expected = [
      'QueryCount' => 0,
      'CacheGetCount' => 1,
      'CacheSetCount' => 0,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 1,
      'ScriptCount' => 1,
      'ScriptBytes' => 11850,
      'StylesheetCount' => 2,
      'StylesheetBytes' => 39500,
    ];
    $this->assertMetrics($expected, $performance_data);
  }

  /**
   * Logs front page tracing data with a lukewarm cache.
   *
   * Cool here means that 'global' site caches are warm but anything
   * specific to the front page is cold.
   */
  protected function testFrontPageCoolCache(): void {
    // First of all visit the front page to ensure the image style exists.
    $this->drupalGet('<front>');
    sleep(2);
    $this->clearCaches();
    // Now visit a different page to warm non-route-specific caches.
    $this->drupalGet('user/login');
    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'umamiFrontPageCoolCache');

    $expected = [
      'QueryCount' => 112,
      'CacheGetCount' => 239,
      'CacheSetCount' => 93,
      'CacheDeleteCount' => 0,
      'CacheTagInvalidationCount' => 0,
      'CacheTagLookupQueryCount' => 31,
      'ScriptCount' => 1,
      'ScriptBytes' => 12000,
      'StylesheetCount' => 2,
      'StylesheetBytes' => 39750,
    ];
    $this->assertMetrics($expected, $performance_data);
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
