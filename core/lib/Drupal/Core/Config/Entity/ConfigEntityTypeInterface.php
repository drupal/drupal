<?php

/**
 * @file
 * Contains \Drupal\Core\Config\Entity\ConfigEntityTypeInterface.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Provides an interface for a configuration entity type and its metadata.
 */
interface ConfigEntityTypeInterface extends EntityTypeInterface {

  /**
   * Length limit of the configuration entity prefix.
   *
   * Configuration entity names are composed of two parts:
   * - The config prefix, which is returned by getConfigPrefix() and is
   *   composed of:
   *   - The provider module name (limited to 50 characters by
   *     DRUPAL_EXTENSION_NAME_MAX_LENGTH).
   *   - The module-specific namespace identifier, which defaults to the
   *     configuration entity type ID. Entity type IDs are limited to 32
   *     characters by EntityTypeInterface::ID_MAX_LENGTH.
   * - The configuration entity ID.
   * So, a typical configuration entity filename will look something like:
   * provider_module_name.namespace_identifier.config_entity_id.yml
   *
   * Most file systems limit a file name's length to 255 characters, so
   * ConfigBase::MAX_NAME_LENGTH restricts the full configuration object name
   * to 250 characters (leaving 5 for the file extension). Therefore, in
   * order to leave sufficient characters to construct a configuration ID,
   * the configuration entity prefix is limited to 83 characters: up to 50
   * characters for the module name, 1 for the dot, and 32 for the namespace
   * identifier. This also allows modules with shorter names to define longer
   * namespace identifiers if desired.
   *
   * @see \Drupal\Core\Config\ConfigBase::MAX_NAME_LENGTH
   * @see \Drupal\Core\Config\Entity\ConfigEntityTypeInterface::getConfigPrefix()
   * @see DRUPAL_EXTENSION_NAME_MAX_LENGTH
   * @see \Drupal\Core\Config\Entity\ConfigEntityStorage::MAX_ID_LENGTH
   * @see \Drupal\Core\Entity\EntityTypeInterface::ID_MAX_LENGTH
   */
  const PREFIX_LENGTH = 83;

  /**
   * Gets the config prefix used by the configuration entity type.
   *
   * The config prefix is used to prefix configuration entity IDs when they are
   * stored in the configuration system. The default config prefix is
   * constructed from the name of the module that provides the entity type and
   * the ID of the entity type. If a config_prefix annotation is present it will
   * be used in place of the entity type ID.
   *
   * Prefixing with the module that provides the configuration entity type
   * ensures that configuration entities depend on the module that provides the
   * configuration entity type.
   *
   * @return string
   *   The config prefix.
   *
   * @throws \Drupal\Core\Config\ConfigPrefixLengthException
   *   Exception thrown when the length of the prefix exceeds PREFIX_LENGTH.
   */
  public function getConfigPrefix();

  /**
   * Gets the config entity properties to export if declared on the annotation.
   *
   * @return array|NULL
   *   The properties to export or NULL if they can not be determine from the
   *   config entity type annotation.
   */
  public function getPropertiesToExport();

  /**
   * Gets the keys that are available for fast lookup.
   *
   * @return string[]
   *   The list of lookup keys.
   */
  public function getLookupKeys();

}
