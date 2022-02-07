<?php

namespace Drupal\serialization;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services tagged 'entity_resolver' to the Serializer.
 */
class RegisterEntityResolversCompilerPass implements CompilerPassInterface {

  /**
   * Adds services to the Serializer.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container to process.
   */
  public function process(ContainerBuilder $container) {
    $definition = $container->getDefinition('serializer.entity_resolver');
    $resolvers = [];

    // Retrieve registered Normalizers and Encoders from the container.
    foreach ($container->findTaggedServiceIds('entity_resolver') as $id => $attributes) {
      $priority = $attributes[0]['priority'] ?? 0;
      $resolvers[$priority][] = new Reference($id);
    }

    // Add the registered concrete EntityResolvers to the ChainEntityResolver.
    foreach ($this->sort($resolvers) as $resolver) {
      $definition->addMethodCall('addResolver', [$resolver]);
    }
  }

  /**
   * Sorts by priority.
   *
   * Order services from highest priority number to lowest (reverse sorting).
   *
   * @param array $services
   *   A nested array keyed on priority number. For each priority number, the
   *   value is an array of Symfony\Component\DependencyInjection\Reference
   *   objects, each a reference to a normalizer or encoder service.
   *
   * @return array
   *   A flattened array of Reference objects from $services, ordered from high
   *   to low priority.
   */
  protected function sort($services) {
    krsort($services);
    return array_merge([], ...$services);
  }

}
