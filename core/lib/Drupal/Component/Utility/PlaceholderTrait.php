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
   * This trait is designed for formatting messages that are mostly text, not as
   * an HTML template language. As such:
   * - The passed in string should contain no (or minimal) HTML.
   * - Variable placeholders should not be used within the "<" and ">" of an
   *   HTML tag, such as in HTML attribute values. This would be a security
   *   risk. Examples:
   *   @code
   *     // Insecure (placeholder within "<" and ">"):
   *     $this->placeholderFormat('<@variable>text</@variable>', ['@variable' => $variable]);
   *     // Insecure (placeholder within "<" and ">"):
   *     $this->placeholderFormat('<a @variable>link text</a>', ['@variable' => $variable]);
   *     // Insecure (placeholder within "<" and ">"):
   *     $this->placeholderFormat('<a title="@variable">link text</a>', ['@variable' => $variable]);
   *   @endcode
   *   Only the "href" attribute is supported via the special ":variable"
   *   placeholder, to allow simple links to be inserted:
   *   @code
   *     // Secure (usage of ":variable" placeholder for href attribute):
   *     $this->placeholderFormat('<a href=":variable">link text</a>', [':variable' , $variable]);
   *     // Secure (usage of ":variable" placeholder for href attribute):
   *     $this->placeholderFormat('<a href=":variable" title="static text">link text</a>', [':variable' => $variable]);
   *     // Insecure (the "@variable" placeholder does not filter dangerous
   *     // protocols):
   *     $this->placeholderFormat('<a href="@variable">link text</a>', ['@variable' => $variable]);
   *     // Insecure ("@variable" placeholder within "<" and ">"):
   *     $this->placeholderFormat('<a href=":url" title="@variable">link text</a>', [':url' => $url, '@variable' => $variable]);
   *   @endcode
   * To build non-minimal HTML, use an HTML template language such as Twig,
   * rather than this trait.
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
   *   - @variable: When the placeholder replacement value is:
   *     - A string, the replaced value in the returned string will be sanitized
   *       using \Drupal\Component\Utility\Html::escape().
   *     - A SafeStringInterface object, the replaced value in the returned
   *       string will not be sanitized.
   *     - A SafeStringInterface object cast to a string, the replaced value in
   *       the returned string be forcibly sanitized using
   *       \Drupal\Component\Utility\Html::escape().
   *       @code
   *         $this->placeholderFormat('This will force HTML-escaping of the replacement value: @text', ['@text' => (string) $safe_string_interface_object));
   *       @endcode
   *     Use this placeholder as the default choice for anything displayed on
   *     the site, but not within HTML attributes, JavaScript, or CSS. Doing so
   *     is a security risk.
   *   - %variable: Use when the replacement value is to be wrapped in <em>
   *     tags.
   *     A call like:
   *     @code
   *       $string = "%output_text";
   *       $arguments = ['output_text' => 'text output here.'];
   *       $this->placeholderFormat($string, $arguments);
   *     @endcode
   *     makes the following HTML code:
   *     @code
   *       <em class="placeholder">text output here.</em>
   *     @endcode
   *     As with @variable, do not use this within HTML attributes, JavaScript,
   *     or CSS. Doing so is a security risk.
   *   - :variable: Return value is escaped with
   *     \Drupal\Component\Utility\Html::escape() and filtered for dangerous
   *     protocols using UrlHelper::stripDangerousProtocols(). Use this when
   *     using the "href" attribute, ensuring the attribute value is always
   *     wrapped in quotes:
   *     @code
   *     // Secure (with quotes):
   *     $this->placeholderFormat('<a href=":url">@variable</a>', [':url' => $url, @variable => $variable]);
   *     // Insecure (without quotes):
   *     $this->placeholderFormat('<a href=:url>@variable</a>', [':url' => $url, @variable => $variable]);
   *     @endcode
   *     When ":variable" comes from arbitrary user input, the result is secure,
   *     but not guaranteed to be a valid URL (which means the resulting output
   *     could fail HTML validation). To guarantee a valid URL, use
   *     Url::fromUri($user_input)->toString() (which either throws an exception
   *     or returns a well-formed URL) before passing the result into a
   *     ":variable" placeholder.
   *
   * @return string
   *   A formatted HTML string with the placeholders replaced.
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
          // Escape if the value is not an object from a class that implements
          // \Drupal\Component\Utility\SafeStringInterface, for example strings
          // will be escaped.
          // \Drupal\Component\Utility\SafeMarkup\SafeMarkup::isSafe() may
          // return TRUE for content that is safe within HTML fragments, but not
          // within other contexts, so this placeholder type must not be used
          // within HTML attributes, JavaScript, or CSS. See
          // \Drupal\Component\Utility\SafeMarkup::format().
          $args[$key] = static::placeholderEscape($value);
          break;

        case ':':
          // Strip URL protocols that can be XSS vectors.
          $value = UrlHelper::stripDangerousProtocols($value);
          // Escape unconditionally, without checking
          // \Drupal\Component\Utility\SafeMarkup\SafeMarkup::isSafe(). This
          // forces characters that are unsafe for use in an "href" HTML
          // attribute to be encoded. If a caller wants to pass a value that is
          // extracted from HTML and therefore is already HTML encoded, it must
          // invoke
          // \Drupal\Component\Utility\OutputStrategyInterface::renderFromHtml()
          // on it prior to passing it in as a placeholder value of this type.
          // @todo Add some advice and stronger warnings.
          //   https://www.drupal.org/node/2569041.
          $args[$key] = Html::escape($value);
          break;

        case '%':
        default:
          // Similarly to @, escape non-safe values. Also, add wrapping markup
          // in order to render as a placeholder. Not for use within attributes,
          // per the warning above about
          // \Drupal\Component\Utility\SafeMarkup\SafeMarkup::isSafe() and also
          // due to the wrapping markup.
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
