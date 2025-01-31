<?php

namespace Drupal\Core\Config;

/**
 * Defines the interface for a configuration factory override object.
 */
interface ConfigFactoryOverrideInterface {

  /**
   * Returns config overrides.
   *
   * @param array $names
   *   A list of configuration names that are being loaded.
   *
   * @return array
   *   An array keyed by configuration name of override data. Override data
   *   contains a nested array structure of overrides.
   */
  public function loadOverrides($names);

  /**
   * The string to append to the configuration static cache name.
   *
   * @return string
   *   A string to append to the configuration static cache name.
   */
  public function getCacheSuffix();

  /**
   * Creates a configuration object for use during install and synchronization.
   *
   * If the overrider stores its overrides in configuration collections then
   * it can have its own implementation of
   * \Drupal\Core\Config\StorableConfigBase. Configuration overriders can link
   * themselves to a configuration collection by listening to the
   * \Drupal\Core\Config\ConfigCollectionEvents::COLLECTION_INFO event and
   * adding the collections they are responsible for. Doing this will allow
   * installation and synchronization to use the overrider's implementation of
   * StorableConfigBase. Additionally, the overrider's implementation should
   * trigger the appropriate event:
   * - Saving and creating triggers ConfigCollectionEvents::SAVE_IN_COLLECTION.
   * - Deleting triggers ConfigCollectionEvents::DELETE_IN_COLLECTION.
   * - Renaming triggers ConfigCollectionEvents::RENAME_IN_COLLECTION.
   *
   * @param string $name
   *   The configuration object name.
   * @param string $collection
   *   The configuration collection.
   *
   * @return \Drupal\Core\Config\StorableConfigBase|null
   *   The configuration object for the provided name and collection. NULL
   *   should be returned when the overrider does not use configuration
   *   collections. For example: a module that provides an overrider to avoid
   *   storing API keys in config would not use collections.
   *
   * @see \Drupal\Core\Config\ConfigCollectionInfo
   * @see \Drupal\Core\Config\ConfigImporter::importConfig()
   * @see \Drupal\Core\Config\ConfigInstaller::createConfiguration()
   * @see \Drupal\Core\Config\ConfigCollectionEvents::SAVE_IN_COLLECTION
   * @see \Drupal\Core\Config\ConfigCollectionEvents::DELETE_IN_COLLECTION
   * @see \Drupal\Core\Config\ConfigCollectionEvents::RENAME_IN_COLLECTION
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION);

  /**
   * Gets the cacheability metadata associated with the config factory override.
   *
   * @param string $name
   *   The name of the configuration override to get metadata for.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   *   A cacheable metadata object.
   */
  public function getCacheableMetadata($name);

}
