<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\FunctionalJavascript;

use Drupal\Core\Cache\Cache;
use Drupal\FunctionalJavascriptTests\PerformanceTestBase;

/**
 * Tests the performance impacts of navigation module.
 *
 * Stark is used as the default theme so that this test is not Olivero specific.
 *
 * @group navigation
 */
class TopBarPerformanceTest extends PerformanceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'navigation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * Tests performance for anonymous users is not affected by the Top Bar.
   */
  public function testTopBarPerformance(): void {
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

    // Gather performance data before enabling navigation_top_bar.
    $performance_data_before_top_bar = $this->collectPerformanceData(function () {
      $this->drupalGet('');
    }, 'navigationFrontPageTopBarDisabled');

    // Install navigation_top_bar module.
    \Drupal::service('module_installer')->install(['navigation_top_bar']);

    // Clear caches to prep for another performance data collect.
    foreach (Cache::getBins() as $bin) {
      $bin->deleteAll();
    }

    // Gather performance data after enabling navigation_top_bar.
    $performance_data_after_top_bar = $this->collectPerformanceData(function () {
      $this->drupalGet('');
    }, 'navigationFrontPageTopBarEnabled');

    // Ensure that there is no change to performance metrics from the Top Bar.
    // Anonymous users should never see the Top Bar.
    $this->assertSame($performance_data_before_top_bar->getQueryCount(), $performance_data_after_top_bar->getQueryCount());
    $this->assertSame($performance_data_before_top_bar->getCacheGetCount(), $performance_data_after_top_bar->getCacheGetCount());
    $this->assertSame($performance_data_before_top_bar->getCacheSetCount(), $performance_data_after_top_bar->getCacheSetCount());
    $this->assertSame($performance_data_before_top_bar->getCacheDeleteCount(), $performance_data_after_top_bar->getCacheDeleteCount());
    $this->assertSame($performance_data_before_top_bar->getCacheTagChecksumCount(), $performance_data_after_top_bar->getCacheTagChecksumCount());
    $this->assertSame($performance_data_before_top_bar->getCacheTagIsValidCount(), $performance_data_after_top_bar->getCacheTagIsValidCount());
    $this->assertSame($performance_data_before_top_bar->getCacheTagInvalidationCount(), $performance_data_after_top_bar->getCacheTagInvalidationCount());
  }

}
