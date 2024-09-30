<?php

namespace Drupal\Core\Language;

use Drupal\Core\Url;

/**
 * Common interface for the language manager service.
 */
interface LanguageManagerInterface {

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
   *   An array of language type machine names.
   */
  public function getLanguageTypes();

  /**
   * Returns information about all defined language types.
   *
   * @return array
   *   An associative array of language type information arrays keyed by
   *   language type machine name, in the format of
   *   hook_language_types_info(). In some implementing classes, this is based
   *   on information from hook_language_types_info() and
   *   hook_language_types_info_alter().
   */
  public function getDefinedLanguageTypesInfo();

  /**
   * Returns the current language for the given type.
   *
   * @param string $type
   *   (optional) The language type; e.g., the interface or the content
   *   language. Defaults to
   *   \Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The current language object for the given type of language.
   */
  public function getCurrentLanguage($type = LanguageInterface::TYPE_INTERFACE);

  /**
   * Resets the given language type or all types if none specified.
   *
   * @param string|null $type
   *   (optional) The language type to reset as a string, e.g.,
   *   LanguageInterface::TYPE_INTERFACE, or NULL to reset all language types.
   *   Defaults to NULL.
   *
   * @return $this
   *   The language manager that has been reset.
   */
  public function reset($type = NULL);

  /**
   * Returns a language object representing the site's default language.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   A language object.
   */
  public function getDefaultLanguage();

  /**
   * Returns a list of languages set up on the site.
   *
   * @param int $flags
   *   (optional) Specifies the state of the languages that have to be returned.
   *   It can be: LanguageInterface::STATE_CONFIGURABLE,
   *   LanguageInterface::STATE_LOCKED, or LanguageInterface::STATE_ALL.
   *
   * @return \Drupal\Core\Language\LanguageInterface[]
   *   An associative array of languages, keyed by the language code.
   */
  public function getLanguages($flags = LanguageInterface::STATE_CONFIGURABLE);

  /**
   * Returns a list of languages set up on the site in their native form.
   *
   * @return \Drupal\Core\Language\LanguageInterface[]
   *   An associative array of languages, keyed by the language code, ordered
   *   by weight ascending and name ascending.
   */
  public function getNativeLanguages();

  /**
   * Returns a language object from the given language code.
   *
   * @param string $langcode
   *   The language code.
   *
   * @return \Drupal\Core\Language\LanguageInterface|null
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
   * @return \Drupal\Core\Language\LanguageInterface[]
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
   * @param array $context
   *   (optional) An associative array of data that can be useful to determine
   *   the fallback sequence. The following keys are used in core:
   *   - langcode: Language code of the desired language.
   *   - operation: The name of the operation indicating the context where
   *     language fallback is being applied. The following operations are
   *     defined in core, but more may be defined in contributed modules:
   *       - entity_view: Invoked when an entity is about to be displayed.
   *         The data key contains the loaded entity.
   *       - views_query: Invoked when a field based views query is performed.
   *         The data key contains a reference to the field object.
   *       - locale_lookup: Invoked when a string translation was not found.
   *         The data key contains the source string.
   *   - data: A data structure that makes sense in the provided
   *     context, see above.
   *
   * @return array
   *   An array of language codes sorted by priority: first values should be
   *   tried first.
   */
  public function getFallbackCandidates(array $context = []);

  /**
   * Returns the language switch links for the given language type.
   *
   * @param string $type
   *   The language type.
   * @param \Drupal\Core\Url $url
   *   The URL the switch links will be relative to.
   *
   * @return object|null
   *   An object with the following keys:
   *   - links: An array of links indexed by the language ID
   *   - method_id: The language negotiation method ID
   *   or NULL if there are no language switch links.
   */
  public function getLanguageSwitchLinks($type, Url $url);

  /**
   * Sets the configuration override language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language to override configuration with.
   *
   * @return $this
   */
  public function setConfigOverrideLanguage(?LanguageInterface $language = NULL);

  /**
   * Gets the current configuration override language.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The current configuration override language.
   */
  public function getConfigOverrideLanguage();

  /**
   * Some common languages with their English and native names.
   *
   * Language codes are defined by the W3C language tags document for
   * interoperability. Language codes typically have a language and, optionally,
   * a script or regional variant name. See:
   * https://www.w3.org/International/articles/language-tags/ for more
   * information.
   *
   * @return array
   *   An array of language code to language name information. Language name
   *   information itself is an array of English and native names.
   */
  public static function getStandardLanguageList();

}
