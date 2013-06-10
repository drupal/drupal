<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\RegisterPathProcessorsPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services to the 'path_processor_manager service.
 */
class RegisterPathProcessorsPass implements CompilerPassInterface {

  /**
   * Adds services tagged 'path_processor_inbound' to the path processor manager.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *  The container to process.
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('path_processor_manager')) {
      return;
    }
    $manager = $container->getDefinition('path_processor_manager');
    // Add inbound path processors.
    foreach ($container->findTaggedServiceIds('path_processor_inbound') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $manager->addMethodCall('addInbound', array(new Reference($id), $priority));
    }
    // Add outbound path processors.
    foreach ($container->findTaggedServiceIds('path_processor_outbound') as $id => $attributes) {
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $manager->addMethodCall('addOutbound', array(new Reference($id), $priority));
    }
  }
}
