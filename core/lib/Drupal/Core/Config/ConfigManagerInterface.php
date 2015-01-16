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
   * Loads a configuration entity using the configuration name.
   *
   * @param string $name
   *   The configuration object name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The configuration entity or NULL if it does not exist.
   */
  public function loadConfigEntityByName($name);

  /**
   * Gets the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager.
   */
  public function getEntityManager();

  /**
   * Gets the config factory.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The entity manager.
   */
  public function getConfigFactory();

  /**
   * Return a formatted diff of a named config between two storages.
   *
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The storage to diff configuration from.
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The storage to diff configuration to.
   * @param string $source_name
   *   The name of the configuration object in the source storage to diff.
   * @param string $target_name
   *   (optional) The name of the configuration object in the target storage.
   *   If omitted, the source name is used.
   * @param string $collection
   *   (optional) The configuration collection name. Defaults to the default
   *   collection.
   *
   * @return core/lib/Drupal/Component/Diff
   *   A formatted string showing the difference between the two storages.
   *
   * @todo Make renderer injectable
   */
  public function diff(StorageInterface $source_storage, StorageInterface $target_storage, $source_name, $target_name = NULL, $collection = StorageInterface::DEFAULT_COLLECTION);

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

  /**
   * Finds config entities that are dependent on extensions or entities.
   *
   * @param string $type
   *   The type of dependency being checked. Either 'module', 'theme', 'config'
   *   or 'content'.
   * @param array $names
   *   The specific names to check. If $type equals 'module' or 'theme' then it
   *   should be a list of module names or theme names. In the case of entity it
   *   should be a list of full configuration object names.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityDependency[]
   *   An array of configuration entity dependency objects.
   */
  public function findConfigEntityDependents($type, array $names);

  /**
   * Finds config entities that are dependent on extensions or entities.
   *
   * @param string $type
   *   The type of dependency being checked. Either 'module', 'theme', 'config'
   *   or 'content'.
   * @param array $names
   *   The specific names to check. If $type equals 'module' or 'theme' then it
   *   should be a list of module names or theme names. In the case of entity it
   *   should be a list of full configuration object names.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityInterface[]
   *   An array of dependencies as configuration entities.
   */
  public function findConfigEntityDependentsAsEntities($type, array $names);

  /**
   * Determines if the provided collection supports configuration entities.
   *
   * @param string $collection
   *   The collection to check.
   *
   * @return bool
   *   TRUE if the collection support configuration entities, FALSE if not.
   */
  public function supportsConfigurationEntities($collection);

  /**
   * Gets available collection information using the event system.
   *
   * @return \Drupal\Core\Config\ConfigCollectionInfo
   *   The object which contains information about the available collections.
   */
  public function getConfigCollectionInfo();

}
