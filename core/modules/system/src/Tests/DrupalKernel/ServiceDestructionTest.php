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
    $this->enableModules(array('service_provider_test'));

    // The service has not been destructed yet.
    $this->assertNull(\Drupal::state()->get('service_provider_test.destructed'));

    // Call the class and then terminate the kernel
    $this->container->get('service_provider_test_class');
    $response = new Response();
    $this->container->get('kernel')->terminate($this->container->get('request'), $response);
    $this->assertTrue(\Drupal::state()->get('service_provider_test.destructed'));
  }

  /**
   * Verifies that services are not unnecessarily destructed when not used.
   */
  public function testDestructionUnused() {
    // Enable the test module to add it to the container.
    $this->enableModules(array('service_provider_test'));

    // The service has not been destructed yet.
    $this->assertNull(\Drupal::state()->get('service_provider_test.destructed'));

    // Terminate the kernel. The test class has not been called, so it should not
    // be destructed.
    $response = new Response();
    $this->container->get('kernel')->terminate($this->container->get('request'), $response);
    $this->assertNull(\Drupal::state()->get('service_provider_test.destructed'));
  }
}
