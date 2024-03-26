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
  public function process(ContainerBuilder $container): void {
    $cache_info['cache']['bins'] = [];
    $cache_info['cache']['default_bin_backends'] = [];
    $cache_info['memory_cache']['bins'] = [];
    $cache_info['memory_cache']['default_bin_backends'] = [];

    $tag_info = [
      'cache.bin' => 'cache',
      'cache.bin.memory' => 'memory_cache',
    ];
    foreach ($tag_info as $service_tag => $section) {
      foreach ($container->findTaggedServiceIds($service_tag) as $id => $attributes) {
        $bin = substr($id, strpos($id, '.') + 1);
        $cache_info[$section]['bins'][$id] = $bin;
        if (isset($attributes[0]['default_backend'])) {
          $cache_info[$section]['default_bin_backends'][$bin] = $attributes[0]['default_backend'];
        }
      }
    }

    $container->setParameter('cache_bins', $cache_info['cache']['bins']);
    $container->setParameter('cache_default_bin_backends', $cache_info['cache']['default_bin_backends']);
    $container->setParameter('memory_cache_bins', $cache_info['memory_cache']['bins']);
    $container->setParameter('memory_cache_default_bin_backends', $cache_info['memory_cache']['default_bin_backends']);
  }

}
