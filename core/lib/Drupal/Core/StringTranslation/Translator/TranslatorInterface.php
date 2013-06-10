<?php

/**
 * @file
 * Contains \Drupal\Core\StringTranslation\Translator\TranslationInterface.
 */

namespace Drupal\Core\StringTranslation\Translator;

/**
 * Interface for objects capable of string translation.
 */
interface TranslatorInterface {

  /**
   * Retrieves English string to given language.
   *
   * @param string $langcode
   *   Language code to translate to.
   * @param string $string
   *   The source string.
   * @param string $context
   *   The string context.
   *
   * @return string|FALSE
   *   Translated string if there is a translation, FALSE if not.
   */
  public function getStringTranslation($langcode, $string, $context);

  /**
   * Resets translation cache.
   *
   * Since most translation systems implement some form of caching, this
   * provides a way to delete that cache.
   */
  public function reset();

}
