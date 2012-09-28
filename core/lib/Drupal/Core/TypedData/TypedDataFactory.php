<?php

/**
 * @file
 * Definition of Drupal\Core\TypedData\TypedDataFactory.
 */

namespace Drupal\Core\TypedData;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * A factory for typed data objects.
 *
 * The factory incorporates list classes if the typed data is a list as well as
 * class overrides that are specified in data definitions.
 */
class TypedDataFactory extends DefaultFactory {

  /**
   * Implements Drupal\Component\Plugin\Factory\FactoryInterface::createInstance().
   *
   * @param string $plugin_id
   *   The id of a plugin, i.e. the data type.
   * @param array $configuration
   *   The plugin configuration, i.e. the data definition.
   *
   * @return Drupal\Core\TypedData\TypedDataInterface
   */
  public function createInstance($plugin_id, array $configuration) {
    $type_definition = $this->discovery->getDefinition($plugin_id);

    // Allow per-data definition overrides of the used classes and generally
    // default to the data type definition.
    $definition = $configuration + $type_definition;

    if (empty($definition['list'])) {
      if (empty($definition['class'])) {
        throw new PluginException(sprintf('The plugin (%s) did not specify an instance class.', $plugin_id));
      }
      $plugin_class = $definition['class'];
    }
    else {
      if (empty($definition['list class'])) {
        throw new PluginException(sprintf('The plugin (%s) did not specify a list instance class.', $plugin_id));
      }
      $plugin_class = $definition['list class'];
    }
    return new $plugin_class($definition, $plugin_id, $this->discovery);
  }
}
