<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services with specific tags to "kernel_destruct_subscriber" service.
 *
 * Only services tagged with "needs_destruction" are added.
 *
 * @see \Drupal\Core\DestructableInterface
 */
class RegisterServicesForDestructionPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    if (!$container->hasDefinition('kernel_destruct_subscriber')) {
      return;
    }

    $definition = $container->getDefinition('kernel_destruct_subscriber');
    $services = $container->findTaggedServiceIds('needs_destruction');
    foreach ($services as $id => $attributes) {
      $definition->addMethodCall('registerService', [$id]);
    }
  }

}
