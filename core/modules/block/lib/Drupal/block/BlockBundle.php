<?php

/**
 * @file
 * Contains \Drupal\block\BlockBundle.
 */

namespace Drupal\Block;

use Drupal\Core\Cache\CacheFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Provides the block dependency injection container.
 */
class BlockBundle extends Bundle {

  /**
   * Overrides \Symfony\Component\HttpKernel\Bundle\Bundle::build().
   */
  public function build(ContainerBuilder $container) {
    // Register the BlockManager class with the dependency injection container.
    $container->register('plugin.manager.block', 'Drupal\block\Plugin\Type\BlockManager')
      ->addArgument('%container.namespaces%');
    CacheFactory::registerBin($container, 'block');
  }

}
