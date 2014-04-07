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
   * Also validates strings as UTF-8.
   *
   * @param string $text
   *   The text to be checked or processed.
   *
   * @return string
   *   An HTML safe version of $text, or an empty string if $text is not
   *   valid UTF-8.
   *
   * @ingroup sanitization
   *
   * @see drupal_validate_utf8()
   */
  public static function checkPlain($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
  }

  /**
   * Decodes all HTML entities including numerical ones to regular UTF-8 bytes.
   *
   * Double-escaped entities will only be decoded once ("&amp;lt;" becomes
   * "&lt;", not "<"). Be careful when using this function, as it will revert
   * previous sanitization efforts (&lt;script&gt; will become <script>).
   *
   * @param string $text
   *   The text to decode entities in.
   *
   * @return string
   *   The input $text, with all HTML entities decoded once.
   */
  public static function decodeEntities($text) {
    return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
  }

  /**
   * Formats a string for HTML display by replacing variable placeholders.
   *
   * This function replaces variable placeholders in a string with the requested
   * values and escapes the values so they can be safely displayed as HTML. It
   * should be used on any unknown text that is intended to be printed to an
   * HTML page (especially text that may have come from untrusted users, since
   * in that case it prevents cross-site scripting and other security problems).
   *
   * In most cases, you should use t() rather than calling this function
   * directly, since it will translate the text (on non-English-only sites) in
   * addition to formatting it.
   *
   * @param $string
   *   A string containing placeholders.
   * @param $args
   *   An associative array of replacements to make. Occurrences in $string of
   *   any key in $args are replaced with the corresponding value, after
   *   optional sanitization and formatting. The type of sanitization and
   *   formatting depends on the first character of the key:
   *   - @variable: Escaped to HTML using String::checkPlain(). Use this as the
   *     default choice for anything displayed on a page on the site.
   *   - %variable: Escaped to HTML and formatted using String::placeholder(),
   *     which makes it display as <em>emphasized</em> text.
   *   - !variable: Inserted as is, with no sanitization or formatting. Only use
   *     this for text that has already been prepared for HTML display (for
   *     example, user-supplied text that has already been run through
   *     String::checkPlain() previously, or is expected to contain some limited
   *     HTML tags and has already been run through
   *     \Drupal\Component\Utility\Xss::filter() previously).
   *
   * @return mixed
   *   The formatted string, or FALSE if no args specified.
   *
   * @ingroup sanitization
   *
   * @see t()
   */
  public static function format($string, array $args = array()) {
    // Transform arguments before inserting them.
    foreach ($args as $key => $value) {
      switch ($key[0]) {
        case '@':
          // Escaped only.
          $args[$key] = static::checkPlain($value);
          break;

        case '%':
        default:
          // Escaped and placeholder.
          $args[$key] = static::placeholder($value);
          break;

        case '!':
          // Pass-through.
      }
    }
    return strtr($string, $args);
  }

  /**
   * Formats text for emphasized display in a placeholder inside a sentence.
   *
   * Used automatically by self::format().
   *
   * @param string $text
   *   The text to format (plain-text).
   *
   * @return string
   *   The formatted text (html).
   */
  public static function placeholder($text) {
    return '<em class="placeholder">' . static::checkPlain($text) . '</em>';
  }

}
