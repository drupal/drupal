<?php

/**
 * @file
 * Contains \Drupal\Core\Update\UpdateServiceProvider.
 */

namespace Drupal\Core\Update;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Ensures for some services that they don't cache.
 */
class UpdateServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $definition = new Definition('Drupal\Core\Cache\NullBackend', ['null']);
    $container->setDefinition('cache.null', $definition);
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('asset.resolver');
    $argument = new Reference('cache.null');
    $definition->replaceArgument(5, $argument);

    $definition = $container->getDefinition('library.discovery.collector');
    $argument = new Reference('cache.null');
    $definition->replaceArgument(0, $argument);
  }

}
