<?php

declare(strict_types=1);

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
  public function testDestructionUsed(): void {
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
  public function testDestructionUnused(): void {
    // Enable the test module to add it to the container.
    $this->enableModules(['service_provider_test']);

    $request = $this->container->get('request_stack')->getCurrentRequest();
    $kernel = $this->container->get('kernel');
    $kernel->preHandle($request);

    // The service has not been destructed yet.
    $this->assertNull(\Drupal::state()->get('service_provider_test.destructed'));

    // Terminate the kernel. The test class has not been called, so it should
    // not be destructed.
    $response = new Response();
    $kernel->terminate($request, $response);
    $this->assertNull(\Drupal::state()->get('service_provider_test.destructed'));
  }

  /**
   * @covers \Drupal\Core\DependencyInjection\Compiler\RegisterServicesForDestructionPass::process
   */
  public function testDestructableServicesOrder(): void {
    // Destructable services before the module is enabled.
    $core_services = $this->container->getParameter('kernel.destructable_services');

    $this->enableModules(['service_provider_test']);
    $services = $this->container->getParameter('kernel.destructable_services');
    // Remove the core registered services for clarity.
    $testable_services = array_values(array_diff($services, $core_services));

    $this->assertSame([
      // Priority 100.
      'service_provider_test_class_5',
      // Priority 50.
      'service_provider_test_class_1',
      // The following two services are both with priority 0 and their order is
      // determined by the order they were registered.
      'service_provider_test_class',
      'service_provider_test_class_3',
      // Priority -10.
      'service_provider_test_class_2',
      // Priority -50.
      'service_provider_test_class_6',
      // Priority -100.
      'service_provider_test_class_4',
    ], $testable_services);
  }

}
