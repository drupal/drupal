<?php

declare(strict_types=1);

namespace Drupal\migrate\Plugin\Discovery;

use Drupal\Component\Plugin\Attribute\AttributeInterface;
use Drupal\Core\Plugin\Discovery\AttributeClassDiscovery;
use Drupal\migrate\Attribute\MultipleProviderAttributeInterface;

/**
 * Determines providers based on the namespaces of a class and its ancestors.
 *
 * @internal
 *   This is a temporary solution to the fact that migration source plugins have
 *   more than one provider. This functionality will be moved to core in
 *   https://www.drupal.org/node/2786355.
 */
class AttributeClassDiscoveryAutomatedProviders extends AttributeClassDiscovery {

  /**
   * Prepares the attribute definition.
   *
   * @param \Drupal\Component\Plugin\Attribute\AttributeInterface $attribute
   *   The attribute derived from the plugin.
   * @param string $class
   *   The class used for the plugin.
   *
   * @throws \LogicException
   *   When the attribute class does not allow for multiple providers.
   */
  protected function prepareAttributeDefinition(AttributeInterface $attribute, string $class): void {
    if (!($attribute instanceof MultipleProviderAttributeInterface)) {
      throw new \LogicException('AttributeClassDiscoveryAutomatedProviders must implement ' . MultipleProviderAttributeInterface::class);
    }
    // @see Drupal\Component\Plugin\Discovery\AttributeClassDiscovery::prepareAttributeDefinition()
    $attribute->setClass($class);

    // Loop through all the parent classes and add their providers (which we
    // infer by parsing their namespaces) to the $providers array.
    $providers = $attribute->getProviders();
    do {
      $providers[] = $this->getProviderFromNamespace($class);
    } while (($class = get_parent_class($class)) !== FALSE);

    $providers = array_diff(array_unique(array_filter($providers)), ['component']);
    $attribute->setProviders($providers);
  }

}
