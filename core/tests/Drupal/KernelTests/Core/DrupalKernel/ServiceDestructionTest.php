<?php

namespace Drupal\KernelTests\Core\DrupalKernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests that services are correctly destructed.
 *
 * @group DrupalKernel
 */
class ServiceDestructionTest extends KernelTestBase {

  /**
   * Verifies that services are destructed when used.
   */
  public function testDestructionUsed() {
    // Enable the test module to add it to the container.
    $this->enableModules(['service_provider_test']);

    $request = $this->container->get('request_stack')->getCurrentRequest();
    $kernel = $this->container->get('kernel');
    $kernel->preHandle($request);

    // The service has not been destructed yet.
    $this->assertNull(\Drupal::state()->get('service_provider_test.destructed'));

    // Call the class and then terminate the kernel
    $this->container->get('service_provider_test_class');

    $response = new Response();
    $kernel->terminate($request, $response);
    $this->assertTrue(\Drupal::state()->get('service_provider_test.destructed'));
  }

  /**
   * Verifies that services are not unnecessarily destructed when not used.
   */
  public function testDestructionUnused() {
    // Enable the test module to add it to the container.
    $this->enableModules(['service_provider_test']);

    $request = $this->container->get('request_stack')->getCurrentRequest();
    $kernel = $this->container->get('kernel');
    $kernel->preHandle($request);

    // The service has not been destructed yet.
    $this->assertNull(\Drupal::state()->get('service_provider_test.destructed'));

    // Terminate the kernel. The test class has not been called, so it should not
    // be destructed.
    $response = new Response();
    $kernel->terminate($request, $response);
    $this->assertNull(\Drupal::state()->get('service_provider_test.destructed'));
  }

}
