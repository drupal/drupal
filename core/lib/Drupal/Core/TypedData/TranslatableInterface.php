<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\TranslatableInterface.
 */

namespace Drupal\Core\TypedData;

/**
 * Interface for translatable data.
 */
interface TranslatableInterface {

  /**
   * Returns the translation language.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The language object.
   */
  public function language();

  /**
   * Checks whether the translation is the default one.
   *
   * @return bool
   *   TRUE if the translation is the default one, FALSE otherwise.
   */
  public function isDefaultTranslation();

  /**
   * Checks whether the translation is new.
   *
   * @return bool
   *   TRUE if the translation is new, FALSE otherwise.
   */
  public function isNewTranslation();

  /**
   * Returns the languages the data is translated to.
   *
   * @param bool $include_default
   *   (optional) Whether the default language should be included. Defaults to
   *   TRUE.
   *
   * @return \Drupal\Core\Language\LanguageInterface[]
   *   An associative array of language objects, keyed by language codes.
   */
  public function getTranslationLanguages($include_default = TRUE);

  /**
   * Gets a translation of the data.
   *
   * The returned translation has to be of the same type than this typed data
   * object.
   *
   * @param $langcode
   *   The language code of the translation to get or
   *   LanguageInterface::LANGCODE_DEFAULT
   *   to get the data in default language.
   *
   * @return $this
   *   A typed data object for the translated data.
   *
   * @throws \InvalidArgumentException
   *   If an invalid or non-existing translation language is specified.
   */
  public function getTranslation($langcode);

  /**
   * Returns the translatable object referring to the original language.
   *
   * @return $this
   *   The translation object referring to the original language.
   */
  public function getUntranslated();

  /**
   * Returns TRUE there is a translation for the given language code.
   *
   * @param string $langcode
   *   The language code identifying the translation.
   *
   * @return bool
   *   TRUE if the translation exists, FALSE otherwise.
   */
  public function hasTranslation($langcode);

  /**
   * Adds a new translation to the translatable object.
   *
   * @param string $langcode
   *   The language code identifying the translation.
   * @param array $values
   *   (optional) An array of initial values to be assigned to the translatable
   *   fields. Defaults to none.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   If an invalid or existing translation language is specified.
   */
  public function addTranslation($langcode, array $values = array());

  /**
   * Removes the translation identified by the given language code.
   *
   * @param string $langcode
   *   The language code identifying the translation to be removed.
   */
  public function removeTranslation($langcode);

  /**
   * Returns the translation support status.
   *
   * @return bool
   *   TRUE if the object has translation support enabled.
   */
  public function isTranslatable();

}
