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
   * Ensures that all configuration entities are prefixed by the module that
   * provides the configuration entity type. This ensures that if a
   * configuration entity is contained in a extension's default configuration,
   * it will be created during extension installation. Additionally, it allows
   * dependencies to be calculated without the modules that provide
   * configuration entity types being installed.
   *
   * @return string|bool
   *   The config prefix, or FALSE if not a configuration entity type.
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

}
