<?php

namespace Drupal\Tests\system\Kernel\System;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the deprecations in the system module.
 *
 * @group system
 * @group legacy
 */
class SystemFunctionsLegacyTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
  ];

  /**
   * @covers ::system_time_zones
   */
  public function testSystemTimeZones() {
    $this->expectDeprecation('system_time_zones() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. This function is no longer used in Drupal core. Use \Drupal\Core\Datetime\TimeZoneFormHelper::getOptionsList(), \Drupal\Core\Datetime\TimeZoneFormHelper::getOptionsListByRegion() or \DateTimeZone::listIdentifiers() instead. See https://www.drupal.org/node/3023528');
    system_time_zones();
  }

}
