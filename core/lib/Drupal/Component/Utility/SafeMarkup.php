<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\SafeMarkup.
 */

namespace Drupal\Component\Utility;

/**
 * Manages known safe strings for rendering at the theme layer.
 *
 * The Twig theme engine autoescapes string variables in the template, so it
 * is possible for a string of markup to become double-escaped. SafeMarkup
 * provides a store for known safe strings and methods to manage them
 * throughout the page request.
 *
 * Strings sanitized by self::checkPlain() or Xss::filter() are automatically
 * marked safe, as are markup strings created from render arrays via
 * drupal_render().
 *
 * This class should be limited to internal use only. Module developers should
 * instead use the appropriate
 * @link sanitization sanitization functions @endlink or the
 * @link theme_render theme and render systems @endlink so that the output can
 * can be themed, escaped, and altered properly.
 *
 * @see TwigExtension::escapeFilter()
 * @see twig_render_template()
 * @see sanitization
 * @see theme_render
 */
class SafeMarkup {

  /**
   * The list of safe strings.
   *
   * @var array
   */
  protected static $safeStrings = array();

  /**
   * Adds a string to a list of strings marked as secure.
   *
   * This method is for internal use. Do not use it to prevent escaping of
   * markup; instead, use the appropriate
   * @link sanitization sanitization functions @endlink or the
   * @link theme_render theme and render systems @endlink so that the output
   * can be themed, escaped, and altered properly.
   *
   * This marks strings as secure for the entire page render, not just the code
   * or element that set it. Therefore, only valid HTML should be
   * marked as safe (never partial markup). For example, you should never do:
   * @code
   *   SafeMarkup::set('<');
   * @endcode
   * or:
   * @code
   *   SafeMarkup::set('<script>');
   * @endcode
   *
   * @param string $string
   *   The content to be marked as secure.
   * @param string $strategy
   *   The escaping strategy used for this string. Two values are supported
   *   by default:
   *   - 'html': (default) The string is safe for use in HTML code.
   *   - 'all': The string is safe for all use cases.
   *   See the
   *   @link http://twig.sensiolabs.org/doc/filters/escape.html Twig escape documentation @endlink
   *   for more information on escaping strategies in Twig.
   *
   * @return string
   *   The input string that was marked as safe.
   */
  public static function set($string, $strategy = 'html') {
    $string = (string) $string;
    static::$safeStrings[$string][$strategy] = TRUE;
    return $string;
  }

  /**
   * Checks if a string is safe to output.
   *
   * @param string $string
   *   The content to be checked.
   * @param string $strategy
   *   The escaping strategy. See self::set(). Defaults to 'html'.
   *
   * @return bool
   *   TRUE if the string has been marked secure, FALSE otherwise.
   */
  public static function isSafe($string, $strategy = 'html') {
    return isset(static::$safeStrings[(string) $string][$strategy]) ||
      isset(static::$safeStrings[(string) $string]['all']);
  }

  /**
   * Adds previously retrieved known safe strings to the safe string list.
   *
   * This is useful for the batch and form APIs, where it is important to
   * preserve the safe markup state across page requests. The strings will be
   * added to any safe strings already marked for the current request.
   *
   * @param array $safe_strings
   *   A list of safe strings as previously retrieved by self::getAll().
   *
   * @throws \UnexpectedValueException
   */
  public static function setMultiple(array $safe_strings) {
    foreach ($safe_strings as $string => $strategies) {
      foreach ($strategies as $strategy => $value) {
        $string = (string) $string;
        if ($value === TRUE) {
          static::$safeStrings[$string][$strategy] = TRUE;
        }
        else {
          // Danger - something is very wrong.
          throw new \UnexpectedValueException('Only the value TRUE is accepted for safe strings');
        }
      }
    }
  }

  /**
   * Encodes special characters in a plain-text string for display as HTML.
   *
   * @param string $string
   *   A string.
   *
   * @return string
   *   The escaped string. If $string was already set as safe with
   *   self::set(), it won't be escaped again.
   */
  public static function escape($string) {
    return static::isSafe($string) ? $string : static::checkPlain($string);
  }

  /**
   * Applies a very permissive XSS/HTML filter for admin-only use.
   *
   * @param string $string
   *   A string.
   *
   * @return string
   *   The escaped string. If $string was already set as safe with
   *   self::set(), it won't be escaped again.
   *
   * @see \Drupal\Component\Utility\Xss::filterAdmin()
   */
  public static function checkAdminXss($string) {
    return static::isSafe($string) ? $string : Xss::filterAdmin($string);
  }

  /**
  * Gets all strings currently marked as safe.
  *
  * This is useful for the batch and form APIs, where it is important to
  * preserve the safe markup state across page requests.
  *
  * @return array
  *   An array of strings currently marked safe.
  */
  public static function getAll() {
    return static::$safeStrings;
  }

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
   *   An HTML safe version of $text, or an empty string if $text is not valid
   *   UTF-8.
   *
   * @ingroup sanitization
   *
   * @see drupal_validate_utf8()
   */
  public static function checkPlain($text) {
    $string = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    static::$safeStrings[$string]['html'] = TRUE;
    return $string;
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
   * @param string $string
   *   A string containing placeholders. The string itself is not escaped, any
   *   unsafe content must be in $args and inserted via placeholders.
   * @param array $args
   *   An associative array of replacements to make. Occurrences in $string of
   *   any key in $args are replaced with the corresponding value, after
   *   optional sanitization and formatting. The type of sanitization and
   *   formatting depends on the first character of the key:
   *   - @variable: Escaped to HTML using self::escape(). Use this as the
   *     default choice for anything displayed on a page on the site.
   *   - %variable: Escaped to HTML and formatted using self::placeholder(),
   *     which makes the following HTML code:
   *     @code
   *       <em class="placeholder">text output here.</em>
   *     @endcode
   *   - !variable: Inserted as is, with no sanitization or formatting. Only
   *     use this when the resulting string is being generated for one of:
   *     - Non-HTML usage, such as a plain-text email.
   *     - Non-direct HTML output, such as a plain-text variable that will be
   *       printed as an HTML attribute value and therefore formatted with
   *       self::checkPlain() as part of that.
   *     - Some other special reason for suppressing sanitization.
   *
   * @return string
   *   The formatted string, which is marked as safe unless sanitization of an
   *   unsafe argument was suppressed (see above).
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
          $args[$key] = static::escape($value);
          break;

        case '%':
        default:
          // Escaped and placeholder.
          $args[$key] = static::placeholder($value);
          break;

        case '!':
          // Pass-through.
          if (!static::isSafe($value)) {
            $safe = FALSE;
          }
      }
    }

    $output = strtr($string, $args);
    if ($safe) {
      static::$safeStrings[$output]['html'] = TRUE;
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
    $string = '<em class="placeholder">' . static::escape($text) . '</em>';
    static::$safeStrings[$string]['html'] = TRUE;
    return $string;
  }

}
