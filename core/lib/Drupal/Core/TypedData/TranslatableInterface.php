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
   * Returns the default language.
   *
   * @return
   *   The language object.
   */
  public function language();

  /**
   * Returns the languages the data is translated to.
   *
   * @param bool $include_default
   *   Whether the default language should be included.
   *
   * @return
   *   An array of language objects, keyed by language codes.
   */
  public function getTranslationLanguages($include_default = TRUE);

  /**
   * Gets a translation of the data.
   *
   * The returned translation has to be implement the same typed data interfaces
   * as this typed data object, excluding the TranslatableInterface. E.g., if
   * this typed data object implements the ComplexDataInterface and
   * AccessibleInterface, the translation object has to implement both as well.
   *
   * @param $langcode
   *   The language code of the translation to get or Language::LANGCODE_DEFAULT
   *   to get the data in default language.
   * @param $strict
   *   (optional) If the data is complex, whether the translation should include
   *   only translatable properties. If set to FALSE, untranslatable properties
   *   are included (in default language) as well as translatable properties in
   *   the specified language. Defaults to TRUE.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   A typed data object for the translated data.
   */
  public function getTranslation($langcode, $strict = TRUE);

}
