<?php

/**
 * @file
 * Contains \Drupal\system\Tests\DrupalKernel\ServiceDestructionTest.
 */

namespace Drupal\system\Tests\DrupalKernel;

use Drupal\simpletest\DrupalUnitTestBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Tests the service destruction functionality.
 */
class ServiceDestructionTest extends DrupalUnitTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Service destruction',
      'description' => 'Tests that services are correctly destructed.',
      'group' => 'DrupalKernel',
    );
  }

  /**
   * Verifies that services are destructed when used.
   */
  public function testDestructionUsed() {
    // Enable the test module to add it to the container.
    $this->enableModules(array('bundle_test'));

    // The service has not been destructed yet.
    $this->assertNull(state()->get('bundle_test.destructed'));

    // Get the service destructor.
    $service_destruction = $this->container->get('kernel_destruct_subscriber');

    // Call the class and then invoke the kernel terminate event.
    $this->container->get('bundle_test_class');
    $response = new Response();
    $event = new PostResponseEvent($this->container->get('kernel'), $this->container->get('request'), $response);
    $service_destruction->onKernelTerminate($event);
    $this->assertTrue(state()->get('bundle_test.destructed'));
  }

  /**
   * Verifies that services are not unnecessarily destructed when not used.
   */
  public function testDestructionUnused() {
    // Enable the test module to add it to the container.
    $this->enableModules(array('bundle_test'));

    // The service has not been destructed yet.
    $this->assertNull(state()->get('bundle_test.destructed'));

    // Get the service destructor.
    $service_destruction = $this->container->get('kernel_destruct_subscriber');

    // Simulate a shutdown. The test class has not been called, so it should not
    // be destructed.
    $response = new Response();
    $event = new PostResponseEvent($this->container->get('kernel'), $this->container->get('request'), $response);
    $service_destruction->onKernelTerminate($event);
    $this->assertNull(state()->get('bundle_test.destructed'));
  }
}
