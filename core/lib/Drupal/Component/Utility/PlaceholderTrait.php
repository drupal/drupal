<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\PlaceholderTrait.
 */

namespace Drupal\Component\Utility;

/**
 * Offers functionality for formatting strings using placeholders.
 */
trait PlaceholderTrait {

  /**
   * Formats a string by replacing variable placeholders.
   *
   * This trait is not intended for passing arbitrary user input into any HTML
   * attribute value, as only URL attributes such as "src" and "href" are
   * supported (using ":variable"). Never use this method on unsafe HTML
   * attributes such as "on*" and "style" and take care when using this with
   * unsupported attributes such as "title" or "alt" as this can lead to
   * unexpected and unsafe output.
   *
   * @param string $string
   *   A string containing placeholders.
   * @param array $args
   *   An associative array of replacements to make. Occurrences in $string of
   *   any key in $args are replaced with the corresponding value, after
   *   optional sanitization and formatting. The type of sanitization and
   *   formatting depends on the first character of the key:
   *   - @variable: Escaped to HTML using Html::escape() unless the value is
   *     already HTML-safe. Use this as the default choice for anything
   *     displayed on a page on the site, but not within HTML attributes.
   *   - %variable: Escaped to HTML just like @variable, but also wrapped in
   *     <em> tags, which makes the following HTML code:
   *     @code
   *       <em class="placeholder">text output here.</em>
   *     @endcode
   *     As with @variable, do not use this within HTML attributes.
   *   - :variable: Escaped to HTML using Html::escape() and filtered for
   *     dangerous protocols using UrlHelper::stripDangerousProtocols(). Use
   *     this when passing in a URL, such as when using the "src" or "href"
   *     attributes, ensuring the value is always wrapped in quotes:
   *     - Secure: <a href=":variable">@variable</a>
   *     - Insecure: <a href=:variable>@variable</a>
   *     When ":variable" comes from arbitrary user input, the result is secure,
   *     but not guaranteed to be a valid URL (which means the resulting output
   *     could fail HTML validation). To guarantee a valid URL, use
   *     Url::fromUri($user_input)->toString() (which either throws an exception
   *     or returns a well-formed URL) before passing the result into a
   *     ":variable" placeholder.
   *
   * @return string
   *   The string with the placeholders replaced.
   *
   * @ingroup sanitization
   *
   * @see \Drupal\Component\Utility\FormattableString
   * @see \Drupal\Core\StringTranslation\TranslatableString
   * @see \Drupal\Core\StringTranslation\PluralTranslatableString
   * @see \Drupal\Component\Utility\Html::escape()
   * @see \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols()
   * @see \Drupal\Core\Url::fromUri()
   */
  protected static function placeholderFormat($string, array $args) {
    // Transform arguments before inserting them.
    foreach ($args as $key => $value) {
      switch ($key[0]) {
        case '@':
          // Escaped only.
          $args[$key] = static::placeholderEscape($value);
          break;

        case ':':
          // URL attributes must be escaped unconditionally (even if they were
          // already marked safe) since content that has been filtered for XSS
          // can still contain characters that are unsafe for use in attributes.
          // @todo decide what to do about non-URL attribute values (#2570431)
          $args[$key] = Html::escape(UrlHelper::stripDangerousProtocols($value));
          break;

        case '%':
        default:
          // Escaped and placeholder.
          $args[$key] = '<em class="placeholder">' . static::placeholderEscape($value) . '</em>';
          break;
      }
    }

    return strtr($string, $args);
  }

  /**
   * Escapes a placeholder replacement value if needed.
   *
   * @param string|\Drupal\Component\Utility\SafeStringInterface $value
   *   A placeholder replacement value.
   *
   * @return string
   *   The properly escaped replacement value.
   */
  protected static function placeholderEscape($value) {
    return SafeMarkup::isSafe($value) ? (string) $value : Html::escape($value);
  }

}
