<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\TypedDataFactory.
 */

namespace Drupal\Core\TypedData;

use InvalidArgumentException;
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
   * Implements \Drupal\Component\Plugin\Factory\FactoryInterface::createInstance().
   *
   * @param string $plugin_id
   *   The id of a plugin, i.e. the data type.
   * @param array $configuration
   *   The plugin configuration, i.e. the data definition.
   * @param string $name
   *   (optional) If a property or list item is to be created, the name of the
   *   property or the delta of the list item.
   * @param mixed $parent
   *   (optional) If a property or list item is to be created, the parent typed
   *   data object implementing either the ListInterface or the
   *   ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The instantiated typed data object.
   */
  public function createInstance($plugin_id, array $configuration, $name = NULL, $parent = NULL) {
    $type_definition = $this->discovery->getDefinition($plugin_id);

    if (!isset($type_definition)) {
      throw new InvalidArgumentException(format_string('Invalid data type %plugin_id has been given.', array('%plugin_id' => $plugin_id)));
    }

    // Allow per-data definition overrides of the used classes, i.e. take over
    // classes specified in the data definition.
    $key = empty($configuration['list']) ? 'class' : 'list class';
    if (isset($configuration[$key])) {
      $class = $configuration[$key];
    }
    elseif (isset($type_definition[$key])) {
      $class = $type_definition[$key];
    }

    if (!isset($class)) {
      throw new PluginException(sprintf('The plugin (%s) did not specify an instance class.', $plugin_id));
    }
    return new $class($configuration, $name, $parent);
  }
}
