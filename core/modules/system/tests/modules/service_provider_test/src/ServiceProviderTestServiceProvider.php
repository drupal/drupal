<?php

/**
 * @file
 * Contains \Drupal\service_provider_test\ServiceProviderTestServiceProvider.
 */

namespace Drupal\service_provider_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\Site\Settings;

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

    if ($indicator = Settings::get('deployment_identifier')) {
      $container->setParameter('container_rebuild_indicator', $indicator);
    }

    if ($parameter = Settings::get('container_rebuild_test_parameter')) {
      $container->setParameter('container_rebuild_test_parameter', $parameter);
    }
  }
}
