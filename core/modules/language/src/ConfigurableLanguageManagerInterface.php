<?php

namespace Drupal\language;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Common interface for language negotiation services.
 */
interface ConfigurableLanguageManagerInterface extends LanguageManagerInterface {

  /**
   * Rebuild the container to register services needed on multilingual sites.
   */
  public static function rebuildServices();

  /**
   * Returns the language negotiator.
   *
   * @return \Drupal\language\LanguageNegotiatorInterface
   *   The language negotiator.
   */
  public function getNegotiator();

  /**
   * Injects the language negotiator.
   *
   * @param \Drupal\language\LanguageNegotiatorInterface $negotiator
   *   The language negotiator.
   */
  public function setNegotiator(LanguageNegotiatorInterface $negotiator);

  /**
   * Returns all the defined language types including fixed ones.
   *
   * A language type maybe configurable or fixed. A fixed language type is a
   * type whose language negotiation methods are module-defined and not altered
   * through the user interface.
   *
   * @return array
   *   An array of language type machine names.
   */
  public function getDefinedLanguageTypes();

  /**
   * Stores language types configuration.
   *
   * @param array $config
   *   An indexed array with the following keys_
   *   - configurable: an array of configurable language type names.
   *   - all: an array of all the defined language type names.
   */
  public function saveLanguageTypesConfiguration(array $config);

  /**
   * Updates locked system language weights.
   */
  public function updateLockedLanguageWeights();

  /**
   * Gets a language config override object.
   *
   * @param string $langcode
   *   The language code for the override.
   * @param string $name
   *   The language configuration object name.
   *
   * @return \Drupal\language\Config\LanguageConfigOverride
   *   The language config override object.
   */
  public function getLanguageConfigOverride($langcode, $name);

  /**
   * Gets a language configuration override storage object.
   *
   * @param string $langcode
   *   The language code for the override.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   A storage object to use for reading and writing the
   *   configuration override.
   */
  public function getLanguageConfigOverrideStorage($langcode);

  /**
   * Returns the standard language list excluding already configured languages.
   *
   * @return array
   *   A list of standard language names keyed by langcode.
   */
  public function getStandardLanguageListWithoutConfigured();

  /**
   * Gets the negotiated language method ID.
   *
   * @param string $type
   *   (optional) The language type; e.g., the interface or the content
   *   language.
   *
   * @return string|null
   *   The negotiated language method ID.
   */
  public function getNegotiatedLanguageMethod($type = LanguageInterface::TYPE_INTERFACE);

}
