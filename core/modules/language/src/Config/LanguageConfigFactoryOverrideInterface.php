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
  public function setLanguage(LanguageInterface $language = NULL);

  /**
   * Get language override for given language and configuration name.
   *
   * @param string $langcode
   *   Language code.
   * @param string $name
   *   Configuration name.
   *
   * @return \Drupal\Core\Config\Config
   *   Configuration override object.
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
