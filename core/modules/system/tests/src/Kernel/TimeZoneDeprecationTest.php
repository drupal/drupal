<?php

namespace Drupal\Tests\system\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated methods in bootstrap throw deprecation warnings.
 *
 * @group legacy
 * @group system
 */
class TimeZoneDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'user',
  ];

  /**
   * @expectedDeprecation drupal_get_user_timezone() is deprecated in drupal:8.8.0. It will be removed from drupal:9.0.0. Use date_default_timezone_get() instead. See https://www.drupal.org/node/3009387
   */
  public function testDeprecation() {
    $this->assertEquals('Australia/Sydney', drupal_get_user_timezone());
  }

}
