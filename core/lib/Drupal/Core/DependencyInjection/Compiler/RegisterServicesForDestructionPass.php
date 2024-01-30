<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services to the "kernel.destructable_services" container parameter.
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
    $services = $container->findTaggedServiceIds('needs_destruction');
    $container->setParameter('kernel.destructable_services', array_keys($services));
  }

}
