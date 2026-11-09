<?php

namespace Drupal\language\Config;

use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the interface for a configuration factory language override object.
 */
interface LanguageConfigFactoryOverrideInterface extends ConfigFactoryOverrideInterface {

  /**
   * Gets the language object used to override configuration data.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The language object used to override configuration data.
   */
  public function getLanguage();

  /**
   * Sets the language to be used in configuration overrides.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object used to override configuration data.
   *
   * @return $this
   */
  public function setLanguage(?LanguageInterface $language = NULL);

  /**
   * Gets the language override for a language and configuration name.
   *
   * An empty object is returned when there is no override data for the given
   * language code and configuration object. This includes cases where:
   * - The configuration has no override for that language.
   * - The given language is not installed or does not exist.
   * - The configuration name is not an existing configuration object.
   *
   * @param string $langcode
   *   The language code for the override.
   * @param string $name
   *   The name of the configuration object.
   *
   * @return \Drupal\language\Config\LanguageConfigOverride
   *   The language config override object for the requested language and
   *   configuration object.
   */
  public function getOverride($langcode, $name);

  /**
   * Returns the storage instance for a particular langcode.
   *
   * @param string $langcode
   *   Language code.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage instance for a particular langcode.
   */
  public function getStorage($langcode);

  /**
   * Installs available language configuration overrides for a given langcode.
   *
   * @param string $langcode
   *   Language code.
   */
  public function installLanguageOverrides($langcode);

}
