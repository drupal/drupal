<?php

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
  public function process(ContainerBuilder $container): void {
    if (!$container->has('kernel')) {
      return;
    }

    $kernel = $container->get('kernel');
    if (!($kernel instanceof DrupalKernelInterface)) {
      return;
    }
    $providers = $kernel->getServiceProviders('app');
    foreach ($providers as $provider) {
      if ($provider instanceof ServiceModifierInterface) {
        $provider->alter($container);
      }
    }
    $providers = $kernel->getServiceProviders('site');
    foreach ($providers as $provider) {
      if ($provider instanceof ServiceModifierInterface) {
        $provider->alter($container);
      }
    }
  }

}
