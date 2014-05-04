<?php

/**
 * @file
 * Contains \Drupal\Core\Language\LanguageManagerInterface.
 */

namespace Drupal\Core\Language;

use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Common interface for the language manager service.
 */
interface LanguageManagerInterface {

  /**
   * Injects the string translation service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation service.
   */
  public function setTranslation(TranslationInterface $translation);

  /**
   * Initializes each language type to a language object.
   */
  public function init();

  /**
   * Returns whether or not the site has more than one language added.
   *
   * @return bool
   *   TRUE if more than one language is added, FALSE otherwise.
   */
  public function isMultilingual();

  /**
   * Returns an array of the available language types.
   *
   * @return array
   *   An array of language type names.
   */
  public function getLanguageTypes();

  /**
   * Returns the current language for the given type.
   *
   * @param string $type
   *   (optional) The language type, e.g. the interface or the content language.
   *   Defaults to \Drupal\Core\Language\Language::TYPE_INTERFACE.
   *
   * @return \Drupal\Core\Language\Language
   *   A language object for the given type.
   */
  public function getCurrentLanguage($type = Language::TYPE_INTERFACE);

  /**
   * Resets the given language type or all types if none specified.
   *
   * @param string|null $type
   *   (optional) The language type to reset as a string, e.g.,
   *   Language::TYPE_INTERFACE, or NULL to reset all language types. Defaults
   *   to NULL.
   *
   * @return \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager that has been reset.
   */
  public function reset($type = NULL);

  /**
   * Returns a language object representing the site's default language.
   *
   * @return \Drupal\Core\Language\Language
   *   A language object.
   */
  public function getDefaultLanguage();

  /**
   * Returns a list of languages set up on the site.
   *
   * @param int $flags
   *   (optional) Specifies the state of the languages that have to be returned.
   *   It can be: Language::STATE_CONFIGURABLE,
   *   Language::STATE_LOCKED, Language::STATE_ALL.
   *
   * @return array
   *   An associative array of languages, keyed by the language code.
   */
  public function getLanguages($flags = Language::STATE_CONFIGURABLE);

  /**
   * Returns a language object from the given language code.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return \Drupal\core\Language\Language|null
   *   A fully-populated language object or NULL.
   */
  public function getLanguage($langcode);

  /**
   * Produced the printed name for a language for display.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return string
   *   The printed name of the language.
   */
  public function getLanguageName($langcode);

  /**
   * Returns a list of the default locked languages.
   *
   * @param int $weight
   *   (optional) An integer value that is used as the start value for the
   *   weights of the locked languages.
   *
   * @return array
   *   An array of language objects.
   */
  public function getDefaultLockedLanguages($weight = 0);

  /**
   * Checks whether a language is locked.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   Returns whether the language is locked.
   */
  public function isLanguageLocked($langcode);

  /**
   * Returns the language fallback candidates for a given context.
   *
   * @param string $langcode
   *   (optional) The language of the current context. Defaults to NULL.
   * @param array $context
   *   (optional) An associative array of data that can be useful to determine
   *   the fallback sequence. The following keys are used in core:
   *   - langcode: The desired language.
   *   - operation: The name of the operation indicating the context where
   *     language fallback is being applied, e.g. 'entity_view'.
   *   - data: An arbitrary data structure that makes sense in the provided
   *     context, e.g. an entity.
   *
   * @return array
   *   An array of language codes sorted by priority: first values should be
   *   tried first.
   */
  public function getFallbackCandidates($langcode = NULL, array $context = array());

  /**
   * Returns the language switch links for the given language type.
   *
   * @param string $type
   *   The language type.
   * @param string $path
   *   The internal path the switch links will be relative to.
   *
   * @return array
   *   A keyed array of links ready to be themed.
   */
  public function getLanguageSwitchLinks($type, $path);

  /**
   * Sets the configuration override language.
   *
   * @param \Drupal\Core\Language\Language $language
   *   The language to override configuration with.
   *
   * @return $this
   */
  public function setConfigOverrideLanguage(Language $language = NULL);

  /**
   * Gets the current configuration override language.
   *
   * @return \Drupal\Core\Language\Language $language
   *   The current configuration override language.
   */
  public function getConfigOverrideLanguage();

}
