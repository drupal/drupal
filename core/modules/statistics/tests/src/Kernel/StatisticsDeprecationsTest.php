<?php

namespace Drupal\Tests\statistics\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecations in the Statistics module.
 *
 * @group statistics
 * @group legacy
 */
class StatisticsDeprecationsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['statistics'];

  /**
   * @expectedDeprecation statistics_get() is deprecated in drupal:8.2.0 and is removed from drupal:9.0.0. Use Drupal::service('statistics.storage.node')->fetchView() instead. See https://www.drupal.org/node/2778245
   */
  public function testStatisticsGetDeprecation() {
    $this->installSchema('statistics', 'node_counter');
    $this->container->get('statistics.storage.node')->recordView(1);
    $expected_timestamp = $this->container->get('datetime.time')->getRequestTime();
    $this->assertSame([
      'totalcount' => 1,
      'daycount' => 1,
      'timestamp' => $expected_timestamp,
    ], statistics_get(1));
  }

}
