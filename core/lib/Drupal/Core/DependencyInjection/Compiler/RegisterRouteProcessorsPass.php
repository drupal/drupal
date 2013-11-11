<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\RegisterRouteProcessorsPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services to the route_processor_manager service.
 */
class RegisterRouteProcessorsPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('route_processor_manager')) {
      return;
    }
    $manager = $container->getDefinition('route_processor_manager');
    // Add outbound route processors.
    foreach ($container->findTaggedServiceIds('route_processor_outbound') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $manager->addMethodCall('addOutbound', array(new Reference($id), $priority));
    }
  }

}
