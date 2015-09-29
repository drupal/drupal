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
   * This method is designed for formatting messages that are mostly text, not
   * as an HTML template language. As such, $string should contain minimal HTML.
   *
   * In $string the placeholders should not be within the "<" and ">" of an HTML
   * tag, except a placeholder starting with : as described below. This would be
   * a security risk.
   *
   * The return value should not be used within the "<" and ">" of an HTML tag,
   * such as in HTML attribute values. Also, do not concatenate the return
   * value into JavaScript or CSS. This would be a security risk.
   *
   * To build HTML that cannot meet these restrictions, use an HTML template
   * language such as Twig, rather than this trait.
   *
   * @param string $string
   *   A string containing placeholders. The string itself is expected to be
   *   safe and correct HTML. Any unsafe content must be in $args and
   *   inserted via placeholders.
   * @param array $args
   *   An associative array of replacements. Each array key should be the same
   *   as a placeholder in $string. The corresponding value should be a string
   *   or an object that implements
   *   \Drupal\Component\Utility\SafeStringInterface. The value replaces the
   *   placeholder in $string. Sanitization and formatting will be done before
   *   replacement. The type of sanitization and formatting depends on the first
   *   character of the key:
   *   - @variable: Use as the default choice for anything displayed on the
   *     site. Do not use within the "<" and ">" of an HTML tag, such as in HTML
   *     attribute values. Doing so is a security risk.
   *     When the placeholder replacement value is:
   *     - A string, it will be sanitized using
   *       \Drupal\Component\Utility\Html::escape().
   *     - A SafeStringInterface object, the object will be cast to a string and
   *       not sanitized. To force sanitization of a SafeStringInterface object,
   *       cast the replacement value to a string, since it is not known what
   *       strategy a SafeStringInterface object uses for santitization.
   *     A call like:
   *     @code
   *       placeholderFormat("Prefix @something", ['@something' => 'text output.']);
   *     @endcode
   *     returns the following HTML string:
   *     @code
   *       Prefix text output.
   *     @endcode
   *   - %variable: Use when @variable would be appropriate, but the placeholder
   *     value will also be wrapped in <em> tags with a class. As with
   *     @variable, do not use within the "<" and ">" of an HTML tag, such as in
   *     HTML attribute values. Doing so is a security risk.
   *     A call like:
   *     @code
   *       placeholderFormat("Prefix %something", ['%something' => 'text output.']);
   *     @endcode
   *     returns the following HTML string:
   *     @code
   *       Prefix <em class="placeholder">text output.</em>
   *     @endcode
   *   - :variable: Use when the return value is to be used as a URL value of an
   *     HTML attribute, for example the href attribute in an a tag.
   *     Return value is escaped with
   *     \Drupal\Component\Utility\Html::escape() and filtered for dangerous
   *     protocols using UrlHelper::stripDangerousProtocols(). Use this when
   *     using the "href" attribute, ensuring the value is always wrapped in
   *     quotes.
   *     When ":variable" comes from arbitrary user input, the result is secure,
   *     but not guaranteed to be a valid URL (which means the resulting output
   *     could fail HTML validation). To guarantee a valid URL, use
   *     Url::fromUri($user_input)->toString() (which either throws an exception
   *     or returns a well-formed URL) before passing the result into a
   *     ":variable" placeholder.
   *     @todo Add some advice and stronger warnings.
   *       https://www.drupal.org/node/2569041
   *
   *   In $string, only HTML attributes that take a URL as the value, are
   *   supported via the special ":variable" placeholder. For example, with the
   *   "href" attribute, the ":variable" placeholder allows simple links to be
   *   inserted:
   *     - Secure: @code <a href=":variable">link text</a> @endcode
   *     - Secure: @code <a href=":variable" title="static text">link text</a> @endcode
   *     - Secure: @code <a href=":variable">@variable</a> @endcode
   *   Insecure $string examples:
   *     - It is insecure in $string to have @ or % placeholders within the "<"
   *       and ">" of an HTML tag:
   *       - Insecure: @code <@variable>text</@variable> @endcode
   *       - Insecure: @code <a @variable>link text</a> @endcode
   *       - Insecure: @code <a href="@variable">link text</a> @endcode
   *       - Insecure: @code <a title="@variable">link text</a> @endcode
   *       - Insecure: @code <a href=":variable" title="@variable">link text</a>@endcode
   *     - The : placeholder should be used for HTML attribute values, but is
   *       insecure not to have quotes around the attribute value:
   *       - Insecure: @code <a href=:variable>@variable</a>@endcode
   *       - Insecure: @code <img src=:variable />@endcode
   *
   * @return string
   *   A formatted string with the placeholders replaced.
   *
   * @ingroup sanitization
   *
   * @see \Drupal\Component\Utility\FormattableString
   * @see \Drupal\Core\StringTranslation\TranslatableString
   * @see \Drupal\Core\StringTranslation\PluralTranslatableString
   * @see \Drupal\Component\Utility\Html::escape()
   * @see \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols()
   * @see \Drupal\Core\Url::fromUri()
   * @see http://stackoverflow.com/questions/2725156/complete-list-of-html-tag-attributes-which-have-a-url-value/2725168#2725168
   */
  protected static function placeholderFormat($string, array $args) {
    // Transform arguments before inserting them.
    foreach ($args as $key => $value) {
      switch ($key[0]) {
        case '@':
          // Escape if the value is not an object from a class that implements
          // \Drupal\Component\Utility\SafeStringInterface, for example strings
          // will be escaped.
          // \Drupal\Component\Utility\SafeMarkup\SafeMarkup::isSafe() may
          // return TRUE for content that is safe within HTML fragments, but not
          // within other contexts, so this placeholder type must not be used
          // within HTML attributes, JavaScript, or CSS.
          $args[$key] = static::placeholderEscape($value);
          break;

        case ':':
          // Strip URL protocols that can be XSS vectors.
          $value = UrlHelper::stripDangerousProtocols($value);
          // Escape unconditionally, without checking
          // \Drupal\Component\Utility\SafeMarkup\SafeMarkup::isSafe(). This
          // forces characters that are unsafe for use in an "href" HTML
          // attribute to be encoded.
          $args[$key] = Html::escape($value);
          break;

        case '%':
        default:
          // Acts like @, but adds wrapping markup: an <em> tag with a class.
          // Not for use within attributes, per the warning above regarding @
          // about \Drupal\Component\Utility\SafeMarkup\SafeMarkup::isSafe() and
          // also due to the wrapping markup.
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
