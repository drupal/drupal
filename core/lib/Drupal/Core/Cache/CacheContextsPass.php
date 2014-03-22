<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\CacheContextsPass.
 */

namespace Drupal\Core\Cache;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds cache_contexts parameter to the container.
 */
class CacheContextsPass implements CompilerPassInterface {

  /**
   * Implements CompilerPassInterface::process().
   *
   * Collects the cache contexts into the cache_contexts parameter.
   */
  public function process(ContainerBuilder $container) {
    $cache_contexts = array_keys($container->findTaggedServiceIds('cache.context'));
    $container->setParameter('cache_contexts', $cache_contexts);
  }

}
