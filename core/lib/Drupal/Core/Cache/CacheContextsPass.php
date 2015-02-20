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
    $cache_contexts = [];
    foreach (array_keys($container->findTaggedServiceIds('cache.context')) as $id) {
      if (strpos($id, 'cache_context.') !== 0) {
        throw new \InvalidArgumentException(sprintf('The service "%s" has an invalid service ID: cache context service IDs must use the "cache_context." prefix. (The suffix is the cache context ID developers may use.)', $id));
      }
      $cache_contexts[] = substr($id, 14);
    }
    $container->setParameter('cache_contexts', $cache_contexts);
  }

}
