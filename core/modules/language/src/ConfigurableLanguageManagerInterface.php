<?php

/**
 * @file
 * Contains \Drupal\language\ConfigurableLanguageManagerInterface
 */

namespace Drupal\language;

use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Common interface for language negotiation services.
 */
interface ConfigurableLanguageManagerInterface extends LanguageManagerInterface {

  /**
   * Rebuild the container to register services needed on multilingual sites.
   */
  public static function rebuildServices();

  /**
   * Injects the request object.
   *
   * @param \Symfony\Component\HttpFoundation\Request
   *   The request object.
   */
  public function setRequest(Request $request);

  /**
   * Returns the language negotiator.
   *
   * @retun \Drupal\language\LanguageNegotiatorInterface
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
   *   An array of language type names.
   */
  public function getDefinedLanguageTypes();

  /**
   * Returns information about all defined language types.
   *
   * @return array
   *   An associative array of language type information arrays keyed by type
   *   names. Based on information from hook_language_types_info().
   *
   * @see hook_language_types_info()
   */
  public function getDefinedLanguageTypesInfo();

  /**
   * Stores language types configuration.
   *
   * @param array
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
   * @return \Drupal\Core\Config\StorageInterface $storage
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

}
