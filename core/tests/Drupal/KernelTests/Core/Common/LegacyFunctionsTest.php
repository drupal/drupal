<?php

namespace Drupal\KernelTests\Core\Common;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests legacy functions in common.inc.
 *
 * @group Common
 * @group legacy
 */
class LegacyFunctionsTest extends KernelTestBase {

  /**
   * Tests format_date().
   *
   * @expectedDeprecation format_date() is deprecated in Drupal 8.0.0 and will be removed before Drupal 9.0.0. Use \Drupal::service('date.formatter')->format() instead. See https://www.drupal.org/node/1876852
   */
  public function testFormatDate() {
    // Provide arguments otherwise the system module configuration is required.
    $date = format_date(0, 'custom', 'Y-m-d');
    $this->assertEquals('1970-01-01', $date);
  }

  /**
   * @expectedDeprecation drupal_set_time_limit() is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. Use \Drupal\Core\Environment::setTimeLimit() instead. See https://www.drupal.org/node/3000058.
   */
  public function testDrupalSetTimeLimit() {
    drupal_set_time_limit(1000);
  }

}
