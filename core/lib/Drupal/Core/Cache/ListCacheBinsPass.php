<?php

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
    $cache_default_bin_backends = array();
    foreach ($container->findTaggedServiceIds('cache.bin') as $id => $attributes) {
      $bin = substr($id, strpos($id, '.') + 1);
      $cache_bins[$id] = $bin;
      if (isset($attributes[0]['default_backend'])) {
        $cache_default_bin_backends[$bin] = $attributes[0]['default_backend'];
      }
    }
    $container->setParameter('cache_bins', $cache_bins);
    $container->setParameter('cache_default_bin_backends', $cache_default_bin_backends);
  }

}
