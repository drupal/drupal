<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\TranslatableInterface.
 */

namespace Drupal\Core\TypedData;

use Drupal\Core\Language\LanguageManager;

/**
 * Interface for translatable data.
 */
interface TranslatableInterface {

  /**
   * Returns the default language.
   *
   * @return \Drupal\Core\Language\Language
   *   The language object.
   */
  public function language();

  /**
   * Returns the languages the data is translated to.
   *
   * @param bool $include_default
   *   (optional) Whether the default language should be included. Defaults to
   *   TRUE.
   *
   * @return
   *   An array of language objects, keyed by language codes.
   */
  public function getTranslationLanguages($include_default = TRUE);

  /**
   * Gets a translation of the data.
   *
   * The returned translation has to be of the same type than this typed data
   * object. If the specified translation does not exist, a new one will be
   * instantiated.
   *
   * @param $langcode
   *   The language code of the translation to get or Language::LANGCODE_DEFAULT
   *   to get the data in default language.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   A typed data object for the translated data.
   */
  public function getTranslation($langcode);

  /**
   * Returns the translatable object referring to the original language.
   *
   * @return \Drupal\Core\TypedData\TranslatableInterface
   *   The translation object referring to the original language.
   */
  public function getUntranslated();

  /**
   * Returns TRUE there is a translation for the given language code.
   *
   * @param string $langcode
   *   The language code identifiying the translation.
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
   * @return \Drupal\Core\TypedData\TranslatableInterface
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
