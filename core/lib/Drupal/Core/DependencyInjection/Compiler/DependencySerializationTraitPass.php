<?php

namespace Drupal\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Sets the _serviceId property on all services.
 *
 * @see \Drupal\Core\DependencyInjection\DependencySerializationTrait
 */
class DependencySerializationTraitPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    $decorations = new \SplPriorityQueue();
    $order = PHP_INT_MAX;

    foreach ($container->getDefinitions() as $service_id => $definition) {
      // Only add the property to services that are public (as private services
      // can not be reloaded through Container::get()) and are objects.
      if (!$definition->hasTag('parameter_service') && $definition->isPublic()) {
        $definition->setProperty('_serviceId', $service_id);
      }

      if ($decorated = $definition->getDecoratedService()) {
        $decorations->insert([$service_id, $definition], [$decorated[2], --$order]);
      }
    }

    foreach ($decorations as list($service_id, $definition)) {
      list($inner, $renamedId) = $definition->getDecoratedService();
      if (!$renamedId) {
        $renamedId = $service_id . '.inner';
      }

      $original = $container->getDefinition($inner);
      if ($original->isPublic()) {
        // The old service is renamed.
        $original->setProperty('_serviceId', $renamedId);
        // The decorating service takes over the old ID.
        $definition->setProperty('_serviceId', $inner);
      }
    }
  }

}
