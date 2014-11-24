<?php

/**
 * @file
 * Contains \Drupal\Core\Config\TypedConfigManagerInterface.
 */

namespace Drupal\Core\Config;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Defines an interface for typed configuration manager.
 *
 * @package Drupal\Core\Config
 */
Interface TypedConfigManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface {

  /**
   * Gets typed configuration data.
   *
   * @param string $name
   *   Configuration object name.
   *
   * @return \Drupal\Core\TypedData\TraversableTypedDataInterface
   *   Typed configuration element.
   */
  public function get($name);

  /**
   * Instantiates a typed configuration object.
   *
   * @param string $data_type
   *   The data type, for which a typed configuration object should be
   *   instantiated.
   * @param array $configuration
   *   The plugin configuration array, i.e. an array with the following keys:
   *   - data definition: The data definition object, i.e. an instance of
   *     \Drupal\Core\TypedData\DataDefinitionInterface.
   *   - name: (optional) If a property or list item is to be created, the name
   *     of the property or the delta of the list item.
   *   - parent: (optional) If a property or list item is to be created, the
   *     parent typed data object implementing either the ListInterface or the
   *     ComplexDataInterface.
   *
   * @return \Drupal\Core\Config\Schema\Element
   *   The instantiated typed configuration object.
   */
  public function createInstance($data_type, array $configuration = array());

  /**
   * Creates a new typed configuration object instance.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition of the typed data object.
   * @param mixed $value
   *   The data value. If set, it has to match one of the supported
   *   data type format as documented for the data type classes.
   * @param string $name
   *   (optional) If a property or list item is to be created, the name of the
   *   property or the delta of the list item.
   * @param mixed $parent
   *   (optional) If a property or list item is to be created, the parent typed
   *   data object implementing either the ListInterface or the
   *   ComplexDataInterface.
   *
   * @return \Drupal\Core\Config\Schema\Element
   *   The instantiated typed data object.
   */
  public function create(DataDefinitionInterface $definition, $value, $name = NULL, $parent = NULL);

  /**
   * Creates a new data definition object from a type definition array and
   * actual configuration data. Since type definitions may contain variables
   * to be replaced, we need the configuration value to create it.
   *
   * @param array $definition
   *   The base type definition array, for which a data definition should be
   *   created.
   * @param $value
   *   Optional value of the configuration element.
   * @param string $name
   *   Optional name of the configuration element.
   * @param object $parent
   *   Optional parent element.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   A data definition for the given data type.
   */
  public function buildDataDefinition(array $definition, $value, $name = NULL, $parent = NULL);

  /**
   * Checks if the configuration schema with the given config name exists.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return bool
   *   TRUE if configuration schema exists, FALSE otherwise.
   */
  public function hasConfigSchema($name);

}
