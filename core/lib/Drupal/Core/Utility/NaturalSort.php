<?php

declare(strict_types=1);

namespace Drupal\Core\Utility;

/**
 * Provides a language-aware replacement for strnatcasecmp().
 */
final class NaturalSort {

  /**
   * The collators used to compare strings, keyed by language code.
   *
   * @var \Collator[]
   */
  private static array $collators = [];

  /**
   * Compares strings using a language-aware "natural order" algorithm.
   *
   * This method is a drop-in replacement for strnatcasecmp(). If the intl
   * extension is available, strings are compared using a \Collator for the
   * given language, so accented characters and other language-specific
   * collation rules are taken into account. Numbers embedded in the strings
   * are compared by their numeric value, as strnatcasecmp() does. If the intl
   * extension is not available, or no language can be determined, this method
   * falls back to strnatcasecmp().
   *
   * @param string $string1
   *   The first string.
   * @param string $string2
   *   The second string.
   * @param string|null $langcode
   *   (optional) The language code to determine the collation rules. Defaults
   *   to the current language if the language manager service is available.
   *
   * @return int
   *   Less than 0 if $string1 sorts before $string2, greater than 0 if
   *   $string1 sorts after $string2, and 0 if they are equal.
   */
  public static function strnatcasecmp(string $string1, string $string2, ?string $langcode = NULL): int {
    if (!extension_loaded('intl')) {
      return strnatcasecmp($string1, $string2);
    }

    if ($langcode === NULL && \Drupal::hasService('language_manager')) {
      $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    if ($langcode === NULL) {
      return strnatcasecmp($string1, $string2);
    }

    if (!isset(self::$collators[$langcode])) {
      $collator = \Collator::create($langcode);
      if ($collator === NULL) {
        return strnatcasecmp($string1, $string2);
      }
      // Compare numbers embedded in the strings by their numeric value, so
      // that for example "10" sorts after "9".
      $collator->setAttribute(\Collator::NUMERIC_COLLATION, \Collator::ON);
      self::$collators[$langcode] = $collator;
    }
    return self::$collators[$langcode]->compare($string1, $string2);
  }

}
