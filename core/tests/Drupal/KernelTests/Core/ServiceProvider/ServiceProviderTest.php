<?php

namespace Drupal\KernelTests\Core\ServiceProvider;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Drupal\Core\Cache\CacheFactory;

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
  protected static $modules = ['file', 'service_provider_test', 'system'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    // Undo cache_factory override done in parent because it can hide caching
    // issues in container build time.
    // @see \Drupal\service_provider_test\ServiceProviderTestServiceProvider::alter()
    $this->container
      ->register('cache_factory', CacheFactory::class)
      ->addArgument(new Reference('settings'))
      ->addArgument(new Parameter('cache_default_bin_backends'))
      ->addMethodCall('setContainer', [new Reference('service_container')]);
  }

  /**
   * Tests that services provided by module service providers get registered to the DIC.
   */
  public function testServiceProviderRegistration() {
    $definition = $this->container->getDefinition('file.usage');
    $this->assertSame('Drupal\\service_provider_test\\TestFileUsage', $definition->getClass(), 'Class has been changed');
    $this->assertTrue(\Drupal::hasService('service_provider_test_class'), 'The service_provider_test_class service has been registered to the DIC');
  }

  /**
   * Tests that the DIC keeps up with module enable/disable in the same request.
   */
  public function testServiceProviderRegistrationDynamic() {
    // Uninstall the module and ensure the service provider's service is not registered.
    \Drupal::service('module_installer')->uninstall(['service_provider_test']);
    $this->assertFalse(\Drupal::hasService('service_provider_test_class'), 'The service_provider_test_class service does not exist in the DIC.');

    // Install the module and ensure the service provider's service is registered.
    \Drupal::service('module_installer')->install(['service_provider_test']);
    $this->assertTrue(\Drupal::hasService('service_provider_test_class'), 'The service_provider_test_class service exists in the DIC.');
  }

}
