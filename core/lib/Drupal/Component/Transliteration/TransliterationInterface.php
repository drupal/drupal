<?php

namespace Drupal\Component\Transliteration;

/**
 * Defines an interface for classes providing transliteration.
 *
 * @ingroup transliteration
 */
interface TransliterationInterface {

  /**
   * Removes diacritics (accents) from certain letters.
   *
   * This only applies to certain letters: Accented Latin characters like
   * a-with-acute-accent, in the UTF-8 character range of 0xE0 to 0xE6 and
   * 01CD to 024F. Replacements that would result in the string changing length
   * are excluded, as well as characters that are not accented US-ASCII letters.
   *
   * @param string $string
   *   The string holding diacritics.
   *
   * @return string
   *   $string with accented letters replaced by their unaccented equivalents.
   */
  public function removeDiacritics($string);

  /**
   * Transliterates text from Unicode to US-ASCII.
   *
   * @param string $string
   *   The string to transliterate.
   * @param string $langcode
   *   (optional) The language code of the language the string is in. Defaults
   *   to 'en' if not provided. Warning: this can be unfiltered user input.
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
