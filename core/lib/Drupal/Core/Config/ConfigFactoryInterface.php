<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigFactoryInterface.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Language\LanguageDefault;

/**
 * Defines the interface for a configuration object factory.
 */
interface ConfigFactoryInterface {

  /**
   * Sets the override state.
   *
   * @param bool $state
   *   TRUE if overrides should be applied, FALSE otherwise.
   *
   * @return $this
   */
  public function setOverrideState($state);

  /**
   * Gets the override state.
   *
   * @return bool
   *   Get the override state.
   */
  public function getOverrideState();

  /**
   * Returns a configuration object for a given name.
   *
   * @param string $name
   *   The name of the configuration object to construct.
   *
   * @return \Drupal\Core\Config\Config
   *   A configuration object.
   */
  public function get($name);

  /**
   * Returns a list of configuration objects for the given names.
   *
   * This will pre-load all requested configuration objects does not create
   * new configuration objects.
   *
   * @param array $names
   *   List of names of configuration objects.
   *
   * @return \Drupal\Core\Config\Config[]
   *   List of successfully loaded configuration objects, keyed by name.
   */
  public function loadMultiple(array $names);

  /**
   * Resets and re-initializes configuration objects. Internal use only.
   *
   * @param string|null $name
   *   (optional) The name of the configuration object to reset. If omitted, all
   *   configuration objects are reset.
   *
   * @return $this
   */
  public function reset($name = NULL);

  /**
   * Renames a configuration object using the storage.
   *
   * @param string $old_name
   *   The old name of the configuration object.
   * @param string $new_name
   *   The new name of the configuration object.
   *
   * @return \Drupal\Core\Config\Config
   *   The renamed config object.
   */
  public function rename($old_name, $new_name);

  /**
   * Gets the cache key for a given config name.
   *
   * @param string $name
   *   The name of the configuration object.
   *
   * @return string
   *   The cache key.
   */
  public function getCacheKey($name);

  /**
   * Gets all the cache keys that match the provided config name.
   *
   * @param string $name
   *   The name of the configuration object.
   *
   * @return array
   *   An array of cache keys that match the provided config name.
   */
  public function getCacheKeys($name);

  /**
   * Clears the config factory static cache.
   *
   * @return $this
   */
  public function clearStaticCache();

  /**
   * Gets configuration object names starting with a given prefix.
   *
   * @see \Drupal\Core\Config\StorageInterface::listAll()
   *
   * @param string $prefix
   *   (optional) The prefix to search for. If omitted, all configuration object
   *   names that exist are returned.
   *
   * @return array
   *   An array containing matching configuration object names.
   */
  public function listAll($prefix = '');

  /**
   * Adds config factory override services.
   *
   * @param \Drupal\Core\Config\ConfigFactoryOverrideInterface $config_factory_override
   *   The config factory override service to add. It is added at the end of the
   *   priority list (lower priority relative to existing ones).
   */
  public function addOverride(ConfigFactoryOverrideInterface $config_factory_override);

}
