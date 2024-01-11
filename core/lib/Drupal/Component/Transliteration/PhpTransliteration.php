<?php

namespace Drupal\Component\Transliteration;

// cspell:ignore Brion Vibber

/**
 * Implements transliteration without using the PECL extensions.
 *
 * Transliterations are done character-by-character, by looking up non-US-ASCII
 * characters in a transliteration database.
 *
 * The database comes from two types of files, both of which are searched for in
 * the PhpTransliteration::$dataDirectory directory. First, language-specific
 * overrides are searched (see PhpTransliteration::readLanguageOverrides()). If
 * there is no language-specific override for a character, the generic
 * transliteration character tables are searched (see
 * PhpTransliteration::readGenericData()). If looking up the character in the
 * generic table results in a NULL value, or an illegal character is
 * encountered, then a substitute character is returned.
 *
 * Some parts of this code were derived from the MediaWiki project's UtfNormal
 * class, Copyright © 2004 Brion Vibber <brion@pobox.com>,
 * http://www.mediawiki.org/
 */
class PhpTransliteration implements TransliterationInterface {

  /**
   * Directory where data for transliteration resides.
   *
   * The constructor sets this (by default) to subdirectory 'data' underneath
   * the directory where the class's PHP file resides.
   *
   * @var string
   */
  protected $dataDirectory;

  /**
   * Associative array of language-specific character transliteration tables.
   *
   * The outermost array keys are language codes. For each language code key,
   * the value is an array whose keys are Unicode character codes, and whose
   * values are the transliterations of those characters to US-ASCII. This is
   * set up as needed in PhpTransliteration::replace() by calling
   * PhpTransliteration::readLanguageOverrides().
   *
   * @var array
   */
  protected $languageOverrides = [];

  /**
   * Non-language-specific transliteration tables.
   *
   * Array whose keys are the upper two bytes of the Unicode character, and
   * whose values are an array of transliterations for each lower-two bytes
   * character code. This is set up as needed in PhpTransliteration::replace()
   * by calling PhpTransliteration::readGenericData().
   *
   * @var array
   */
  protected $genericMap = [];

  /**
   * Special characters for ::removeDiacritics().
   *
   * Characters which have accented variants but their base character
   * transliterates to more than one ASCII character require special
   * treatment: we want to remove their accent and use the un-
   * transliterated base character.
   */
  protected $fixTransliterateForRemoveDiacritics = [
    'AE' => 'Æ',
    'ae' => 'æ',
    'ZH' => 'Ʒ',
    'zh' => 'ʒ',
  ];

  /**
   * Constructs a transliteration object.
   *
   * @param string $data_directory
   *   (optional) The directory where data files reside. If omitted, defaults
   *   to subdirectory 'data' underneath the directory where the class's PHP
   *   file resides.
   */
  public function __construct($data_directory = NULL) {
    $this->dataDirectory = (isset($data_directory)) ? $data_directory : __DIR__ . '/data';
  }

  /**
   * {@inheritdoc}
   */
  public function removeDiacritics($string) {
    $result = '';

    foreach (preg_split('//u', $string, 0, PREG_SPLIT_NO_EMPTY) as $character) {
      $code = self::ordUTF8($character);

      // These two Unicode ranges include the accented US-ASCII letters, with a
      // few characters that aren't accented letters mixed in. So define the
      // ranges and the excluded characters.
      $range1 = $code > 0x00bf && $code < 0x017f;
      $exclusions_range1 = [0x00d0, 0x00d7, 0x00f0, 0x00f7, 0x0138, 0x014a, 0x014b];
      $range2 = $code > 0x01cc && $code < 0x0250;
      $exclusions_range2 = [0x01DD, 0x01f7, 0x021c, 0x021d, 0x0220, 0x0221, 0x0241, 0x0242, 0x0245];

      $replacement = $character;
      if (($range1 && !in_array($code, $exclusions_range1)) || ($range2 && !in_array($code, $exclusions_range2))) {
        $to_add = $this->lookupReplacement($code, 'xyz');
        if (strlen($to_add) === 1) {
          $replacement = $to_add;
        }
        elseif (isset($this->fixTransliterateForRemoveDiacritics[$to_add])) {
          $replacement = $this->fixTransliterateForRemoveDiacritics[$to_add];
        }
      }

      $result .= $replacement;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function transliterate($string, $langcode = 'en', $unknown_character = '?', $max_length = NULL) {
    $result = '';
    $length = 0;
    $hash = FALSE;

    // Replace question marks with a unique hash if necessary. This because
    // mb_convert_encoding() replaces all invalid characters with a question
    // mark.
    if ($unknown_character != '?' && str_contains($string, '?')) {
      $hash = hash('sha256', $string);
      $string = str_replace('?', $hash, $string);
    }

    // Ensure the string is valid UTF8 for preg_split(). Unknown characters will
    // be replaced by a question mark.
    $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

    // Use the provided unknown character instead of a question mark.
    if ($unknown_character != '?') {
      $string = str_replace('?', $unknown_character, $string);
      // Restore original question marks if necessary.
      if ($hash !== FALSE) {
        $string = str_replace($hash, '?', $string);
      }
    }

    // Split into Unicode characters and transliterate each one.
    foreach (preg_split('//u', $string, 0, PREG_SPLIT_NO_EMPTY) as $character) {
      $code = self::ordUTF8($character);
      if ($code == -1) {
        $to_add = $unknown_character;
      }
      else {
        $to_add = $this->replace($code, $langcode, $unknown_character);
      }

      // Check if this exceeds the maximum allowed length.
      if (isset($max_length)) {
        $length += strlen($to_add);
        if ($length > $max_length) {
          // There is no more space.
          return $result;
        }
      }

      $result .= $to_add;
    }

    return $result;
  }

  /**
   * Finds the character code for a UTF-8 character: like ord() but for UTF-8.
   *
   * @param string $character
   *   A single UTF-8 character.
   *
   * @return int
   *   The character code, or -1 if an illegal character is found.
   */
  protected static function ordUTF8($character) {
    $first_byte = ord($character[0]);

    if (($first_byte & 0x80) == 0) {
      // Single-byte form: 0xxxxxxxx.
      return $first_byte;
    }
    if (($first_byte & 0xe0) == 0xc0) {
      // Two-byte form: 110xxxxx 10xxxxxx.
      return (($first_byte & 0x1f) << 6) + (ord($character[1]) & 0x3f);
    }
    if (($first_byte & 0xf0) == 0xe0) {
      // Three-byte form: 1110xxxx 10xxxxxx 10xxxxxx.
      return (($first_byte & 0x0f) << 12) + ((ord($character[1]) & 0x3f) << 6) + (ord($character[2]) & 0x3f);
    }
    if (($first_byte & 0xf8) == 0xf0) {
      // Four-byte form: 11110xxx 10xxxxxx 10xxxxxx 10xxxxxx.
      return (($first_byte & 0x07) << 18) + ((ord($character[1]) & 0x3f) << 12) + ((ord($character[2]) & 0x3f) << 6) + (ord($character[3]) & 0x3f);
    }

    // Other forms are not legal.
    return -1;
  }

  /**
   * Replaces a single Unicode character using the transliteration database.
   *
   * @param int $code
   *   The character code of a Unicode character.
   * @param string $langcode
   *   The language code of the language the character is in.
   * @param string $unknown_character
   *   The character to substitute for characters without transliterated
   *   equivalents.
   *
   * @return string
   *   US-ASCII replacement character. If it has a mapping, it is returned;
   *   otherwise, $unknown_character is returned. The replacement can contain
   *   multiple characters.
   */
  protected function replace($code, $langcode, $unknown_character) {
    if ($code < 0x80) {
      // Already lower ASCII.
      return chr($code);
    }

    // See if there is a language-specific override for this character.
    if (!isset($this->languageOverrides[$langcode])) {
      $this->readLanguageOverrides($langcode);
    }
    if (isset($this->languageOverrides[$langcode][$code])) {
      return $this->languageOverrides[$langcode][$code];
    }

    return $this->lookupReplacement($code, $unknown_character);
  }

  /**
   * Look up the generic replacement for a UTF-8 character code.
   *
   * @param $code
   *   The UTF-8 character code.
   * @param string $unknown_character
   *   (optional) The character to substitute for characters without entries in
   *   the replacement tables.
   *
   * @return string
   *   US-ASCII replacement characters. If it has a mapping, it is returned;
   *   otherwise, $unknown_character is returned. The replacement can contain
   *   multiple characters.
   */
  protected function lookupReplacement($code, $unknown_character = '?') {
    // See if there is a generic mapping for this character.
    $bank = $code >> 8;
    if (!isset($this->genericMap[$bank])) {
      $this->readGenericData($bank);
    }
    $code = $code & 0xff;
    return $this->genericMap[$bank][$code] ?? $unknown_character;
  }

  /**
   * Reads in language overrides for a language code.
   *
   * The data is read from files named "$langcode.php" in
   * PhpTransliteration::$dataDirectory. These files should set up an array
   * variable $overrides with an element whose key is $langcode and whose value
   * is an array whose keys are character codes, and whose values are their
   * transliterations in this language. The character codes can be for any valid
   * Unicode character, independent of the number of bytes.
   *
   * @param $langcode
   *   Code for the language to read.
   */
  protected function readLanguageOverrides($langcode) {
    // Figure out the file name to use by sanitizing the language code,
    // just in case.
    $file = $this->dataDirectory . '/' . preg_replace('/[^a-zA-Z\-]/', '', $langcode) . '.php';

    // Read in this file, which should set up a variable called $overrides,
    // which will be local to this function.
    $overrides[$langcode] = [];
    if (is_file($file)) {
      include $file;
    }
    $this->languageOverrides[$langcode] = $overrides[$langcode];
  }

  /**
   * Reads in generic transliteration data for a bank of characters.
   *
   * The data is read in from a file named "x$bank.php" (with $bank in
   * hexadecimal notation) in PhpTransliteration::$dataDirectory. These files
   * should set up a variable $bank containing an array whose numerical indices
   * are the remaining two bytes of the character code, and whose values are the
   * transliterations of these characters into US-ASCII. Note that the maximum
   * Unicode character that can be encoded in this way is 4 bytes.
   *
   * @param $bank
   *   First two bytes of the Unicode character, or 0 for the ASCII range.
   */
  protected function readGenericData($bank) {
    // Figure out the file name.
    $file = $this->dataDirectory . '/x' . sprintf('%02x', $bank) . '.php';

    // Read in this file, which should set up a variable called $base, which
    // will be local to this function.
    $base = [];
    if (is_file($file)) {
      include $file;
    }
    $this->genericMap[$bank] = $base;
  }

}
