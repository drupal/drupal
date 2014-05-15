<?php

/**
 * @file
 * Contains Drupal\Core\DependencyInjection\Compiler\ModifyServiceDefinitionsPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Drupal\Core\DrupalKernelInterface;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Passes the container to the alter() method of all service providers.
 */
class ModifyServiceDefinitionsPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->has('kernel')) {
      return;
    }
    $kernel = $container->get('kernel');
    if (!($kernel instanceof DrupalKernelInterface)) {
      return;
    }
    $providers = $kernel->getServiceProviders();
    foreach ($providers as $provider) {
      if ($provider instanceof ServiceModifierInterface) {
        $provider->alter($container);
      }
    }
  }

}
