<?php

namespace Drupal\serialization;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Adds services tagged 'normalizer' and 'encoder' to the Serializer.
 */
class RegisterSerializationClassesCompilerPass implements CompilerPassInterface {

  /**
   * Adds services to the Serializer.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container to process.
   */
  public function process(ContainerBuilder $container) {
    $definition = $container->getDefinition('serializer');

    // Retrieve registered Normalizers and Encoders from the container.
    foreach ($container->findTaggedServiceIds('normalizer') as $id => $attributes) {
      // The 'serializer' service is the public API: mark normalizers private.
      $container->getDefinition($id)->setPublic(FALSE);

      $priority = $attributes[0]['priority'] ?? 0;
      $normalizers[$priority][] = new Reference($id);
    }
    foreach ($container->findTaggedServiceIds('encoder') as $id => $attributes) {
      // The 'serializer' service is the public API: mark encoders private.
      $container->getDefinition($id)->setPublic(FALSE);

      $priority = $attributes[0]['priority'] ?? 0;
      $encoders[$priority][] = new Reference($id);
    }

    // Add the registered Normalizers and Encoders to the Serializer.
    if (!empty($normalizers)) {
      $definition->replaceArgument(0, $this->sort($normalizers));
    }
    if (!empty($encoders)) {
      $definition->replaceArgument(1, $this->sort($encoders));
    }

    // Find all serialization formats known.
    $formats = [];
    $format_providers = [];
    foreach ($container->findTaggedServiceIds('encoder') as $service_id => $attributes) {
      $format = $attributes[0]['format'];
      $formats[] = $format;

      if ($provider_tag = $container->getDefinition($service_id)->getTag('_provider')) {
        $format_providers[$format] = $provider_tag[0]['provider'];
      }
    }
    $container->setParameter('serializer.formats', $formats);
    $container->setParameter('serializer.format_providers', $format_providers);
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
