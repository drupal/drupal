<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\RegisterLazyRouteFilters.
 */


namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers all lazy route filters onto the lazy route filter.
 */
class RegisterLazyRouteFilters implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('route_filter.lazy_collector')) {
      return;
    }

    $service_ids = [];

    foreach ($container->findTaggedServiceIds('route_filter') as $id => $attributes) {
      $service_ids[$id] = $id;
    }

    $container
      ->getDefinition('route_filter.lazy_collector')
      ->addArgument($service_ids);
  }

}
