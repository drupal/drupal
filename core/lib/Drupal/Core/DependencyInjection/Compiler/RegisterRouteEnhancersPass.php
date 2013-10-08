<?php

/**
 * @file
 * Contains Drupal\Core\DependencyInjection\Compiler\RegisterRouteEnhancersPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers route enhancer services with the router.
 */
class RegisterRouteEnhancersPass implements CompilerPassInterface {

  /**
   * Adds services tagged with "route_enhancer" to the router.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container to process.
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('router.dynamic')) {
      return;
    }

    $router = $container->getDefinition('router.dynamic');
    foreach ($container->findTaggedServiceIds('route_enhancer') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $router->addMethodCall('addRouteEnhancer', array(new Reference($id), $priority));
    }
  }
}
