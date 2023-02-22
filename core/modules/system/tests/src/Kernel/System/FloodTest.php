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
   * Tests flood control memory backend.
   */
  public function testMemoryBackend() {
    $threshold = 1;
    $window_expired = -1;
    $name = 'flood_test_cleanup';

    $request_stack = \Drupal::service('request_stack');
    $flood = new MemoryBackend($request_stack);
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
   * Tests memory backend records events to the nearest microsecond.
   */
  public function testMemoryBackendThreshold() {
    $request_stack = \Drupal::service('request_stack');
    $flood = new MemoryBackend($request_stack);
    $flood->register('new event');
    $this->assertTrue($flood->isAllowed('new event', '2'));
    $flood->register('new event');
    $this->assertFalse($flood->isAllowed('new event', '2'));
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

}
