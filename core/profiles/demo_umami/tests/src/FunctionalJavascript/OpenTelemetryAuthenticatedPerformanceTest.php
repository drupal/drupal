<?php

declare(strict_types=1);

namespace Drupal\Tests\demo_umami\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\PerformanceTestBase;

/**
 * Tests demo_umami profile performance.
 *
 * @group OpenTelemetry
 * @group #slow
 */
class OpenTelemetryAuthenticatedPerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  protected function setUp(): void {
    parent::setUp();
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);
  }

  /**
   * Logs front page tracing data with an authenticated user and warm cache.
   */
  public function testFrontPageAuthenticatedWarmCache(): void {
    $this->drupalGet('<front>');
    $this->drupalGet('<front>');

    $performance_data = $this->collectPerformanceData(function () {
      $this->drupalGet('<front>');
    }, 'authenticatedFrontPage');
    $this->assertLessThanOrEqual(16, $performance_data->getQueryCount());
    $this->assertGreaterThanOrEqual(15, $performance_data->getQueryCount());
    $this->assertSame(15, $performance_data->getCacheGetCount());
    $this->assertSame(0, $performance_data->getCacheSetCount());
    $this->assertSame(0, $performance_data->getCacheDeleteCount());
  }

}
