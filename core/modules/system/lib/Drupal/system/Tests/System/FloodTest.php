<?php

/**
 * @file
 * Definition of Drupal\system\Tests\System\FloodTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Functional tests for the flood control mechanism.
 */
class FloodTest extends WebTestBase {

  /**
   * The Request object that flood classes should use.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  public static function getInfo() {
    return array(
      'name' => 'Flood control mechanism',
      'description' => 'Functional tests for the flood control mechanism.',
      'group' => 'System',
    );
  }

  public function setUp() {
    parent::setUp();

    // Flood backends need a request object. Create a dummy one and insert it
    // to the container.
    $this->request = Request::createFromGlobals();
    $this->container->set('request', $this->request);
  }

  /**
   * Test flood control mechanism clean-up.
   */
  function testCleanUp() {
    $threshold = 1;
    $window_expired = -1;
    $name = 'flood_test_cleanup';

    // Register expired event.
    $flood = \Drupal::service('flood');
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
  function testMemoryBackend() {
    $threshold = 1;
    $window_expired = -1;
    $name = 'flood_test_cleanup';

    $flood = new \Drupal\Core\Flood\MemoryBackend($this->request);
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
