<?php

namespace Drupal\Core\TypedData;

/**
 * Defines an interface for checking the status of an entity translation.
 */
interface TranslationStatusInterface {

  /**
   * Status code identifying a removed translation.
   */
  const TRANSLATION_REMOVED = 0;

  /**
   * Status code identifying an existing translation.
   */
  const TRANSLATION_EXISTING = 1;

  /**
   * Status code identifying a newly created translation.
   */
  const TRANSLATION_CREATED = 2;

  /**
   * Returns the translation status.
   *
   * @param string $langcode
   *   The language code identifying the translation.
   *
   * @return int|null
   *   One of the TRANSLATION_* constants or NULL if the given translation does
   *   not exist.
   */
  public function getTranslationStatus($langcode);

}
