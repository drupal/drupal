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
    drupal_container()->get('flood')->register($name, $window_expired);
    // Verify event is not allowed.
    $this->assertFalse(drupal_container()->get('flood')->isAllowed($name, $threshold));
    // Run cron and verify event is now allowed.
    $this->cronRun();
    $this->assertTrue(drupal_container()->get('flood')->isAllowed($name, $threshold));

    // Register unexpired event.
    drupal_container()->get('flood')->register($name);
    // Verify event is not allowed.
    $this->assertFalse(drupal_container()->get('flood')->isAllowed($name, $threshold));
    // Run cron and verify event is still not allowed.
    $this->cronRun();
    $this->assertFalse(drupal_container()->get('flood')->isAllowed($name, $threshold));
  }

  /**
   * Test flood control memory backend.
   */
  function testMemoryBackend() {
    $threshold = 1;
    $window_expired = -1;
    $name = 'flood_test_cleanup';

    $flood = new \Drupal\Core\Flood\MemoryBackend;
    // Register expired event.
    $flood->register($name, $window_expired);
    // Verify event is not allowed.
    $this->assertFalse($flood->isAllowed($name, $threshold));
    // Run cron and verify event is now allowed.
    $flood->garbageCollection();
    $this->assertTrue($flood->isAllowed($name, $threshold));

    // Register unexpired event.
    $flood->register($name);
    // Verify event is not allowed.
    $this->assertFalse($flood->isAllowed($name, $threshold));
    // Run cron and verify event is still not allowed.
    $flood->garbageCollection();
    $this->assertFalse($flood->isAllowed($name, $threshold));
  }
}
