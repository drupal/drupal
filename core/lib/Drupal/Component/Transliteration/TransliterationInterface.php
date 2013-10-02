<?php

/**
 * @file
 * Definition of \Drupal\Component\Transliteration\TransliterationInterface.
 */

namespace Drupal\Component\Transliteration;

/**
 * Defines an interface for classes providing transliteration.
 *
 * @ingroup transliteration
 */
interface TransliterationInterface {

  /**
   * Transliterates text from Unicode to US-ASCII.
   *
   * @param string $string
   *   The string to transliterate.
   * @param string $langcode
   *   (optional) The language code of the language the string is in. Defaults
   *   to 'en' if not provided.
   * @param string $unknown_character
   *   (optional) The character to substitute for characters in $string without
   *   transliterated equivalents. Defaults to '?'.
   * @param int $max_length
   *   (optional) If provided, return at most this many characters, ensuring
   *   that the transliteration does not split in the middle of an input
   *   character's transliteration.
   *
   * @return string
   *   $string with non-US-ASCII characters transliterated to US-ASCII
   *   characters, and unknown characters replaced with $unknown_character.
   */
  public function transliterate($string, $langcode = 'en', $unknown_character = '?', $max_length = NULL);
}
