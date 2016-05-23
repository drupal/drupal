<?php

namespace Drupal\KernelTests\Core\ServiceProvider;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests service provider registration to the DIC.
 *
 * @group ServiceProvider
 */
class ServiceProviderTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file', 'service_provider_test', 'system');

  /**
   * Tests that services provided by module service providers get registered to the DIC.
   */
  function testServiceProviderRegistration() {
    $definition = $this->container->getDefinition('file.usage');
    $this->assertTrue($definition->getClass() == 'Drupal\\service_provider_test\\TestFileUsage', 'Class has been changed');
    $this->assertTrue(\Drupal::hasService('service_provider_test_class'), 'The service_provider_test_class service has been registered to the DIC');
  }

  /**
   * Tests that the DIC keeps up with module enable/disable in the same request.
   */
  function testServiceProviderRegistrationDynamic() {
    // Uninstall the module and ensure the service provider's service is not registered.
    \Drupal::service('module_installer')->uninstall(array('service_provider_test'));
    $this->assertFalse(\Drupal::hasService('service_provider_test_class'), 'The service_provider_test_class service does not exist in the DIC.');

    // Install the module and ensure the service provider's service is registered.
    \Drupal::service('module_installer')->install(array('service_provider_test'));
    $this->assertTrue(\Drupal::hasService('service_provider_test_class'), 'The service_provider_test_class service exists in the DIC.');
  }

}
