<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\FloodTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Functional tests for the flood control mechanism.
 *
 * @group system
 */
class FloodTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Flood backends need a request object. Create a dummy one and insert it
    // to the container.
    $request = Request::createFromGlobals();
    $this->container->get('request_stack')->push($request);
  }

  /**
   * Test flood control mechanism clean-up.
   */
  public function testCleanUp() {
    $threshold = 1;
    $window_expired = -1;
    $name = 'flood_test_cleanup';

    // Register expired event.
    $flood = \Drupal::flood();
    $flood->register($name, $window_expired);
    // Verify event is not allowed.
    $this->assertFalse($flood->isAllowed($name, $threshold));
    // Run cron and verify event is now allowed.
    $this->cronRun();
    $this->assertTrue($flood->isAllowed($name, $threshold));

    // Register unexpired event.
    $flood->register($name);
    // Verify event is not allowed.
    $this->assertFalse($flood->isAllowed($name, $threshold));
    // Run cron and verify event is still not allowed.
    $this->cronRun();
    $this->assertFalse($flood->isAllowed($name, $threshold));
  }

  /**
   * Test flood control memory backend.
   */
  public function testMemoryBackend() {
    $threshold = 1;
    $window_expired = -1;
    $name = 'flood_test_cleanup';

    $request_stack = \Drupal::service('request_stack');
    $flood = new \Drupal\Core\Flood\MemoryBackend($request_stack);
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
   * Test flood control database backend.
   */
  public function testDatabaseBackend() {
    $threshold = 1;
    $window_expired = -1;
    $name = 'flood_test_cleanup';

    $connection = \Drupal::service('database');
    $request_stack = \Drupal::service('request_stack');
    $flood = new \Drupal\Core\Flood\DatabaseBackend($connection, $request_stack);
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
