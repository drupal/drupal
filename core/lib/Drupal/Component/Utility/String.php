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
   * Also validates strings as UTF-8. All processed strings are also
   * automatically flagged as safe markup strings for rendering.
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
   * @see \Drupal\Component\Utility\SafeMarkup
   */
  public static function checkPlain($text) {
    return SafeMarkup::set(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
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
   * @return mixed
   *   The formatted string, or FALSE if no args specified.
   *
   * @ingroup sanitization
   *
   * @see t()
   */
  public static function format($string, array $args = array()) {
    $safe = TRUE;

    // Transform arguments before inserting them.
    foreach ($args as $key => $value) {
      switch ($key[0]) {
        case '@':
          // Escaped only.
          $args[$key] = SafeMarkup::escape($value);
          break;

        case '%':
        default:
          // Escaped and placeholder.
          $args[$key] = static::placeholder($value);
          break;

        case '!':
          // Pass-through.
          if (!SafeMarkup::isSafe($value)) {
            $safe = FALSE;
          }
      }
    }

    $output = strtr($string, $args);
    if ($safe) {
      SafeMarkup::set($output);
    }

    return $output;
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
    return SafeMarkup::set('<em class="placeholder">' . SafeMarkup::escape($text) . '</em>');
  }


}
