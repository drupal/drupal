<?php

/**
 * @file
 * Contains \Drupal\Core\Cache\ListCacheBinsPass.
 */

namespace Drupal\Core\Cache;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds cache_bins parameter to the container.
 */
class ListCacheBinsPass implements CompilerPassInterface {

  /**
   * Implements CompilerPassInterface::process().
   *
   * Collects the cache bins into the cache_bins parameter.
   */
  public function process(ContainerBuilder $container) {
    $cache_bins = array();
    foreach ($container->findTaggedServiceIds('cache.bin') as $id => $attributes) {
      $cache_bins[$id] = substr($id, strpos($id, '.') + 1);
    }
    $container->setParameter('cache_bins', $cache_bins);
  }
}
