<?php

namespace Drupal\Core\Config;

use Drupal\Core\TypedData\TypedDataManagerInterface;

/**
 * Defines an interface for managing config schema type plugins.
 *
 * @see \Drupal\Core\Config\TypedConfigManager
 * @see \Drupal\Core\Config\Schema\ConfigSchemaDiscovery
 * @see hook_config_schema_info_alter()
 * @see https://www.drupal.org/node/1905070
 */
interface TypedConfigManagerInterface extends TypedDataManagerInterface {

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

  /**
   * Gets a specific plugin definition.
   *
   * @param string $plugin_id
   *   A plugin id.
   * @param bool $exception_on_invalid
   *   Ignored with TypedConfigManagerInterface. Kept for compatibility with
   *   DiscoveryInterface.
   *
   * @return array
   *   A plugin definition array. If the given plugin id does not have typed
   *   configuration definition assigned, the definition of an undefined
   *   element type is returned.
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE);

}
