<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\String.
 */

namespace Drupal\Component\Utility;

/**
 * Provides helpers to operate on strings.
 *
 * @ingroup utility
 */
class String {

  /**
   * Encodes special characters in a plain-text string for display as HTML.
   *
   * @param string $text
   *   The text to be checked or processed.
   *
   * @return string
   *   An HTML safe version of $text, or an empty string if $text is not
   *   valid UTF-8.
   *
   * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
   *   Use \Drupal\Component\Utility\SafeMarkup::checkPlain() instead.
   */
  public static function checkPlain($text) {
    return SafeMarkup::checkPlain($text);
  }

  /**
   * Decodes all HTML entities including numerical ones to regular UTF-8 bytes.
   *
   * @param string $text
   *   The text to decode entities in.
   *
   * @return string
   *   The input $text, with all HTML entities decoded once.
   *
   * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
   *   Use \Drupal\Component\Utility\Html::decodeEntities() instead.
   */
  public static function decodeEntities($text) {
    return Html::decodeEntities($text);
  }

  /**
   * Formats a string for HTML display by replacing variable placeholders.
   *
   * @param $string
   *   A string containing placeholders. The string itself is not escaped, any
   *   unsafe content must be in $args and inserted via placeholders.
   * @param $args
   *   An associative array of replacements to make. Occurrences in $string of
   *   any key in $args are replaced with the corresponding value, after
   *   optional sanitization and formatting. The type of sanitization and
   *   formatting depends on the first character of the key:
   *   - @variable: Escaped to HTML using
   *     \Drupal\Component\Utility\SafeMarkup::escape(). Use this as the
   *     default choice for anything displayed on a page on the site.
   *   - %variable: Escaped to HTML and formatted using String::placeholder(),
   *     which makes the following HTML code:
   *     @code
   *       <em class="placeholder">text output here.</em>
   *     @endcode
   *   - !variable: Inserted as is, with no sanitization or formatting. Only
   *     use this when the resulting string is being generated for one of:
   *     - Non-HTML usage, such as a plain-text email.
   *     - Non-direct HTML output, such as a plain-text variable that will be
   *       printed as an HTML attribute value and therefore formatted with
   *       String::checkPlain() as part of that.
   *     - Some other special reason for suppressing sanitization.
   *
   * @return string
   *   The formatted string, which is marked as safe unless sanitization of an
   *   unsafe argument was suppressed (see above).
   *
   * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
   *   Use \Drupal\Component\Utility\SafeMarkup::format() instead.
   */
  public static function format($string, array $args = array()) {
    return SafeMarkup::format($string, $args);
  }

  /**
   * Formats text for emphasized display in a placeholder inside a sentence.
   *
   * @param string $text
   *   The text to format (plain-text).
   *
   * @return string
   *   The formatted text (html).
   *
   * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
   *   Use \Drupal\Component\Utility\SafeMarkup::placeholder() instead.
   */
  public static function placeholder($text) {
    return SafeMarkup::placeholder($text);
  }

}
