<?php

namespace Drupal\Tests\system\Kernel\System;

use Drupal\Core\Flood\DatabaseBackend;
use Drupal\Core\Flood\MemoryBackend;
use Drupal\KernelTests\KernelTestBase;

/**
 * Functional tests for the flood control mechanism.
 *
 * @group system
 */
class FloodTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests flood control mechanism clean-up.
   */
  public function testCleanUp() {
    $threshold = 1;
    $window_expired = -1;
    $name = 'flood_test_cleanup';
    $cron = $this->container->get('cron');

    $flood = \Drupal::flood();
    $this->assertTrue($flood->isAllowed($name, $threshold));
    // Register expired event.
    $flood->register($name, $window_expired);
    // Verify event is not allowed.
    $this->assertFalse($flood->isAllowed($name, $threshold));
    // Run cron and verify event is now allowed.
    $cron->run();
    $this->assertTrue($flood->isAllowed($name, $threshold));

    // Register unexpired event.
    $flood->register($name);
    // Verify event is not allowed.
    $this->assertFalse($flood->isAllowed($name, $threshold));
    // Run cron and verify event is still not allowed.
    $cron->run();
    $this->assertFalse($flood->isAllowed($name, $threshold));
  }

  /**
   * Tests flood control database backend.
   */
  public function testDatabaseBackend() {
    $threshold = 1;
    $window_expired = -1;
    $name = 'flood_test_cleanup';

    $connection = \Drupal::service('database');
    $request_stack = \Drupal::service('request_stack');
    $flood = new DatabaseBackend($connection, $request_stack);
    $this->assertTrue($flood->isAllowed($name, $threshold));
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

  /**
   * Provides an array of backends for testClearByPrefix.
   */
  public function floodBackendProvider() :array {
    $request_stack = \Drupal::service('request_stack');
    $connection = \Drupal::service('database');

    return [
      new MemoryBackend($request_stack),
      new DatabaseBackend($connection, $request_stack),
    ];
  }

  /**
   * Tests clearByPrefix method on flood backends.
   */
  public function testClearByPrefix() {
    $threshold = 1;
    $window_expired = 3600;
    $identifier = 'prefix-127.0.0.1';
    $name = 'flood_test_cleanup';

    // We can't use an PHPUnit data provider because we need access to the
    // container.
    $backends = $this->floodBackendProvider();

    foreach ($backends as $backend) {
      // Register unexpired event.
      $backend->register($name, $window_expired, $identifier);
      // Verify event is not allowed.
      $this->assertFalse($backend->isAllowed($name, $threshold, $window_expired, $identifier));
      // Clear by prefix and verify event is now allowed.
      $backend->clearByPrefix($name, 'prefix');
      $this->assertTrue($backend->isAllowed($name, $threshold));
    }
  }

}
