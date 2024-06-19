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
class OpenTelemetryFrontPagePerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * Logs front page tracing data with a cold cache.
   */
  public function testFrontPageColdCache(): void {
    // @todo Chromedriver doesn't collect tracing performance logs for the very
    //   first request in a test, so warm it up.
    //   https://www.drupal.org/project/drupal/issues/3379750
    $this->drupalGet('user/login');
    $this->rebuildAll();
    $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'umamiFrontPageColdCache');
    $this->assertSession()->pageTextContains('Umami');
  }

  /**
   * Logs front page tracing data with a hot cache.
   *
   * Hot here means that all possible caches are warmed.
   */
  public function testFrontPageHotCache(): void {
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
    $this->assertSame(0, $performance_data->getQueryCount());
    $this->assertSame(1, $performance_data->getCacheGetCount());
    $this->assertSame(0, $performance_data->getCacheSetCount());
    $this->assertSame(0, $performance_data->getCacheDeleteCount());
    $this->assertSame(0, $performance_data->getCacheTagChecksumCount());
    $this->assertSame(1, $performance_data->getCacheTagIsValidCount());
    $this->assertSame(0, $performance_data->getCacheTagInvalidationCount());
    $this->assertSame(1, $performance_data->getScriptCount());
    $this->assertLessThan(7500, $performance_data->getScriptBytes());
    $this->assertSame(2, $performance_data->getStylesheetCount());
    $this->assertLessThan(40400, $performance_data->getStylesheetBytes());
  }

  /**
   * Logs front page tracing data with a lukewarm cache.
   *
   * Cool here means that 'global' site caches are warm but anything
   * specific to the front page is cold.
   */
  public function testFrontPageCoolCache(): void {
    // First of all visit the front page to ensure the image style exists.
    $this->drupalGet('<front>');
    $this->rebuildAll();
    // Now visit a different page to warm non-route-specific caches.
    $this->drupalGet('user/login');
    $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'umamiFrontPageCoolCache');
  }

}
