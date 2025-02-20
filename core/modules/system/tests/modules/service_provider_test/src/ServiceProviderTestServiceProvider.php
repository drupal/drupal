<?php

declare(strict_types=1);

namespace Drupal\service_provider_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\Site\Settings;

/**
 * Service provider for service provider testing module.
 */
class ServiceProviderTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('file.usage')) {
      // Override the class used for the file.usage service.
      $definition = $container->getDefinition('file.usage');
      $definition->setClass('Drupal\service_provider_test\TestFileUsage');
    }

    // Make sure a cached service can be also called in a service provider.
    // https://www.drupal.org/project/drupal/issues/2363351
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $container->get('module_handler');
    try {
      $this_module_relative_path = $module_handler->getModule('service_provider_test')->getPath();
      $container->setParameter('service_provider_test_path', $this_module_relative_path);
    }
    catch (\Exception) {
      throw new \LogicException('Unable to identify installation path of this module.');
    }

    if ($indicator = Settings::get('deployment_identifier')) {
      $container->setParameter('container_rebuild_indicator', $indicator);
    }

    if ($parameter = Settings::get('container_rebuild_test_parameter')) {
      $container->setParameter('container_rebuild_test_parameter', $parameter);
    }
  }

}
