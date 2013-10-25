<?php

/**
 * @file
 * Definition of Drupal\service_provider_test\ServiceProviderTestServiceProvider.
 */

namespace Drupal\service_provider_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

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
  }
}
