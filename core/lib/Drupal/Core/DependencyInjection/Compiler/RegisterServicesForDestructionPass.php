<?php

/**
 * @file
 * Contains \Drupal\Core\DependencyInjection\Compiler\RegisterServicesForDestructionPass.
 */

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services tagged "needs_destruction" to the "kernel_destruct_subscriber"
 * service.
 *
 * @see \Drupal\Core\DestructableInterface
 */
class RegisterServicesForDestructionPass implements CompilerPassInterface {

  /**
   * Implements \Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface::process().
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('kernel_destruct_subscriber')) {
      return;
    }

    $definition = $container->getDefinition('kernel_destruct_subscriber');

    $services = $container->findTaggedServiceIds('needs_destruction');
    foreach ($services as $id => $attributes) {
      $definition->addMethodCall('registerService', array($id));
    }
  }
}
