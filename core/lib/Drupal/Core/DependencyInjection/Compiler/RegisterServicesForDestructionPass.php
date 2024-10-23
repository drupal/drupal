<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
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

  use PriorityTaggedServiceTrait;

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $service_ids = array_values(array_map(strval(...), $this->findAndSortTaggedServices('needs_destruction', $container)));
    $container->setParameter('kernel.destructable_services', $service_ids);
  }

}
