<?php

/**
 * @file
 * Contains \Drupal\Core\Config\ConfigFactoryInterface.
 */

namespace Drupal\Core\Config;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageDefault;

/**
 * Defines the interface for a configuration object factory.
 */
interface ConfigFactoryInterface {

  /**
   * Prefix for all language configuration files.
   */
  const LANGUAGE_CONFIG_PREFIX = 'language.config';

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
   * Renames a configuration object using the storage controller.
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
   * Sets the language to be used in configuration overrides.
   *
   * @param \Drupal\Core\Language\Language|null $language
   *   The language object to be set on the config factory. Used to override
   *   configuration by language.
   *
   * @return $this
   */
  public function setLanguage(Language $language = NULL);

  /**
   * Sets the language for configuration overrides using the default language.
   *
   * @param \Drupal\Core\Language\LanguageDefault $language_default
   *   The default language service. This sets the initial language on the
   *   config factory to the site's default. The language can be used to
   *   override configuration data if language overrides are available.
   *
   * @return $this
   */
  public function setLanguageFromDefault(LanguageDefault $language_default);

  /**
   * Gets the language Used to override configuration.
   *
   * @return \Drupal\Core\Language\Language
   */
  public function getLanguage();

  /**
   * Gets configuration names for this language.
   *
   * It will be the same name with a prefix depending on language code:
   * language.config.LANGCODE.NAME
   *
   * @param array $names
   *   A list of configuration object names.
   *
   * @return array
   *   The localized config names, keyed by configuration object name.
   */
  public function getLanguageConfigNames(array $names);

  /**
   * Gets configuration name for the provided language.
   *
   * The name will be the same name with a prefix depending on language code:
   * language.config.LANGCODE.NAME
   *
   * @param string $langcode
   *   The language code.
   * @param string $name
   *   The name of the configuration object.
   *
   * @return bool|string
   *   The configuration name for configuration object providing overrides.
   *   Returns false if the name already starts with the language config prefix.
   */
  public function getLanguageConfigName($langcode, $name);

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
