<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigManagerInterface.
 */

namespace Drupal\Core\Config;

/**
 * Provides an interface for configuration manager.
 */
interface ConfigManagerInterface {

  /**
   * Returns the entity type of a configuration object.
   *
   * @param string $name
   *   The configuration object name.
   *
   * @return string|null
   *   Either the entity type name, or NULL if none match.
   */
  public function getEntityTypeIdByName($name);

  /**
   * Gets the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager.
   */
  public function getEntityManager();

  /**
   * Return a formatted diff of a named config between two storages.
   *
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The storage to diff configuration from.
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The storage to diff configuration to.
   * @param string $name
   *   The name of the configuration object to diff.
   *
   * @return core/lib/Drupal/Component/Diff
   *   A formatted string showing the difference between the two storages.
   *
   * @todo Make renderer injectable
   */
  public function diff(StorageInterface $source_storage, StorageInterface $target_storage, $name);

  /**
   * Creates a configuration snapshot following a successful import.
   *
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The storage to synchronize configuration from.
   * @param \Drupal\Core\Config\StorageInterface $snapshot_storage
   *   The storage to synchronize configuration to.
   */
  public function createSnapshot(StorageInterface $source_storage, StorageInterface $snapshot_storage);

  /**
   * Uninstalls the configuration of a given extension.
   *
   * @param string $type
   *   The extension type; e.g., 'module' or 'theme'.
   * @param string $name
   *   The name of the module or theme to install configuration for.
   */
  public function uninstall($type, $name);

}
