<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigFactoryOverrideInterface.
 */

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
   * \Drupal\Core\Config\ConfigEvents::COLLECTION_INFO event and adding the
   * collections they are responsible for. Doing this will allow installation
   * and synchronization to use the overrider's implementation of
   * StorableConfigBase.
   *
   * @see \Drupal\Core\Config\ConfigCollectionInfo
   * @see \Drupal\Core\Config\ConfigImporter::importConfig()
   * @see \Drupal\Core\Config\ConfigInstaller::createConfiguration()
   *
   * @param string $name
   *   The configuration object name.
   * @param string $collection
   *   The configuration collection.
   *
   * @return \Drupal\Core\Config\StorableConfigBase
   *   The configuration object for the provided name and collection.
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION);

}
