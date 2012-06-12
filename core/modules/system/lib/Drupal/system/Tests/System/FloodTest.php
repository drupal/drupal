<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\FloodTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for the flood control mechanism.
 */
class FloodTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Flood control mechanism',
      'description' => 'Functional tests for the flood control mechanism.',
      'group' => 'System',
    );
  }

  /**
   * Test flood control mechanism clean-up.
   */
  function testCleanUp() {
    $threshold = 1;
    $window_expired = -1;
    $name = 'flood_test_cleanup';

    // Register expired event.
    flood_register_event($name, $window_expired);
    // Verify event is not allowed.
    $this->assertFalse(flood_is_allowed($name, $threshold));
    // Run cron and verify event is now allowed.
    $this->cronRun();
    $this->assertTrue(flood_is_allowed($name, $threshold));

    // Register unexpired event.
    flood_register_event($name);
    // Verify event is not allowed.
    $this->assertFalse(flood_is_allowed($name, $threshold));
    // Run cron and verify event is still not allowed.
    $this->cronRun();
    $this->assertFalse(flood_is_allowed($name, $threshold));
  }
}
