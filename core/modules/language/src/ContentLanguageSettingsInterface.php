<?php

namespace Drupal\language;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining language settings for content entities.
 */
interface ContentLanguageSettingsInterface extends ConfigEntityInterface {

  /**
   * Gets the entity type ID this config applies to.
   *
   * @return string
   *   The entity type ID this config applies to.
   */
  public function getTargetEntityTypeId();

  /**
   * Gets the bundle this config applies to.
   *
   * @return string
   *   The bundle this config applies to.
   */
  public function getTargetBundle();

  /**
   * Sets the bundle this config applies to.
   *
   * @param string $target_bundle
   *   The bundle.
   *
   * @return $this
   */
  public function setTargetBundle($target_bundle);

  /**
   * Sets the default language code.
   *
   * @param string $default_langcode
   *   The default language code.
   *
   * @return $this
   */
  public function setDefaultLangcode($default_langcode);

  /**
   * Gets the default language code.
   *
   * @return string
   *   The default language code.
   */
  public function getDefaultLangcode();

  /**
   * Sets if the language must be alterable or not.
   *
   * @param bool $language_alterable
   *   Flag indicating if the language must be alterable.
   *
   * @return $this
   */
  public function setLanguageAlterable($language_alterable);

  /**
   * Checks if the language is alterable or not.
   *
   * @return bool
   *   Flag indicating if the language is alterable.
   */
  public function isLanguageAlterable();

  /**
   * Checks if this config object contains the default values in every property.
   *
   * @return bool
   *   True if all the properties contain the default values. False otherwise.
   */
  public function isDefaultConfiguration();

}
