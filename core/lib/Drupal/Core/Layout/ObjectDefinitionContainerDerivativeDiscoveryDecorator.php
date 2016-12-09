<?php

namespace Drupal\Core\Layout;

use Drupal\Component\Plugin\Exception\InvalidDeriverException;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;

/**
 * Allows object-based definitions to use derivatives.
 *
 * @internal
 *   The layout system is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 *
 * @todo In https://www.drupal.org/node/2821189 merge into
 *    \Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator.
 */
class ObjectDefinitionContainerDerivativeDiscoveryDecorator extends ContainerDerivativeDiscoveryDecorator {

  /**
   * {@inheritdoc}
   */
  protected function getDeriverClass($base_definition) {
    $class = NULL;
    if ($base_definition instanceof DerivablePluginDefinitionInterface && $class = $base_definition->getDeriver()) {
      if (!class_exists($class)) {
        throw new InvalidDeriverException(sprintf('Plugin (%s) deriver "%s" does not exist.', $base_definition['id'], $class));
      }
      if (!is_subclass_of($class, '\Drupal\Component\Plugin\Derivative\DeriverInterface')) {
        throw new InvalidDeriverException(sprintf('Plugin (%s) deriver "%s" must implement \Drupal\Component\Plugin\Derivative\DeriverInterface.', $base_definition['id'], $class));
      }
    }
    return $class;
  }

}
