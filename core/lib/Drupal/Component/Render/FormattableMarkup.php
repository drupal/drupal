<?php

namespace Drupal\Component\Render;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;

/**
 * Formats a string for HTML display by replacing variable placeholders.
 *
 * When cast to a string, this object replaces variable placeholders in the
 * string with the arguments passed in during construction and escapes the
 * values so they can be safely displayed as HTML. See the documentation of
 * \Drupal\Component\Render\FormattableMarkup::placeholderFormat() for details
 * on the supported placeholders and how to use them securely. Incorrect use of
 * this class can result in security vulnerabilities.
 *
 * In most cases, you should use TranslatableMarkup or PluralTranslatableMarkup
 * rather than this object, since they will translate the text (on
 * non-English-only sites) in addition to formatting it. Variables concatenated
 * without the insertion of language-specific words or punctuation are some
 * examples where translation is not applicable and using this class directly
 * is appropriate.
 *
 * This class is designed for formatting messages that are mostly text, not as
 * an HTML template language. As such:
 * - The passed in string should contain no (or minimal) HTML.
 * - The result from casting an object to a string should not be used within
 *   the "<" and ">" of an HTML tag, such as in HTML attribute values. This
 *   would be a security risk.
 * To build non-minimal HTML, use an HTML template language such as Twig,
 * rather than this class.
 *
 * @ingroup sanitization
 *
 * @see \Drupal\Core\StringTranslation\TranslatableMarkup
 * @see \Drupal\Core\StringTranslation\PluralTranslatableMarkup
 * @see \Drupal\Component\Render\FormattableMarkup::placeholderFormat()
 */
class FormattableMarkup implements MarkupInterface, \Countable {

  /**
   * The string containing placeholders.
   *
   * @var string
   */
  protected $string;

  /**
   * The arguments to replace placeholders with.
   *
   * @var array
   */
  protected $arguments = [];

  /**
   * Constructs a new class instance.
   *
   * @param string $string
   *   A string containing placeholders. The string itself will not be escaped,
   *   any unsafe content must be in $args and inserted via placeholders.
   * @param array $arguments
   *   An array with placeholder replacements, keyed by placeholder. See
   *   \Drupal\Component\Render\FormattableMarkup::placeholderFormat() for
   *   additional information about correct and secure use of placeholders.
   *
   * @see \Drupal\Component\Render\FormattableMarkup::placeholderFormat()
   */
  public function __construct($string, array $arguments) {
    $this->string = (string) $string;
    $this->arguments = $arguments;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return static::placeholderFormat($this->string, $this->arguments);
  }

  /**
   * Returns the string length.
   *
   * @return int
   *   The length of the string.
   */
  public function count(): int {
    return mb_strlen($this->string);
  }

  /**
   * Returns a representation of the object for use in JSON serialization.
   *
   * @return string
   *   The safe string content.
   */
  public function jsonSerialize(): string {
    return $this->__toString();
  }

  /**
   * Replaces placeholders in a string with values.
   *
   * For convenience examples are listed here. Refer to the parameter
   * description for $args for details of the placeholders "@", "%", and ":".
   *
   * Secure examples.
   * @code
   * // Return the HTML string "Prefix $some_variable".
   * $this->placeholderFormat('Prefix @foo', ['@foo' => $some_variable]);
   * // Convert an object to a sanitized string.
   * $this->placeholderFormat('Non-sanitized replacement value: @foo', ['@foo' => (string) $safe_string_interface_object]);
   * // Wrap $some_variable in an <em> tag.
   * $this->placeholderFormat('Prefix %foo', ['%foo' => $some_variable]);
   * // The following are using the : placeholder inside an HTML tag.
   * $this->placeholderFormat('<a href=":foo">link text</a>', [':foo' => $some_variable]);
   * $this->placeholderFormat('<a href=":foo" title="static text">link text</a>', [':foo' => $some_variable]);
   * $this->placeholderFormat('<a href=":foo">link text</a>', [':foo' => $some_variable]);
   * // Use a : placeholder inside an HTML tag.
   * $this->placeholderFormat('<img src=":foo" />', [':foo' => '/image.png']);
   * @endcode
   * The above are typical examples of using the placeholders correctly.
   *
   * Insecure examples.
   * @code
   * // The following are using the @ placeholder inside an HTML tag.
   * $this->placeholderFormat('<@foo>text</@foo>', ['@foo' => $some_variable]);
   * $this->placeholderFormat('<a @foo>link text</a>', ['@foo' => $some_variable]);
   * $this->placeholderFormat('<a href="@foo">link text</a>', ['@foo' => $some_variable]);
   * $this->placeholderFormat('<a title="@foo">link text</a>', ['@foo' => $some_variable]);
   * // Implicitly convert an object to a string, which is not sanitized.
   * $this->placeholderFormat('Non-sanitized replacement value: @foo', ['@foo' => $safe_string_interface_object]);
   * @endcode
   * These are the more common mistakes that can be made. Make sure that your
   * site is not using any insecure usages of these placeholders.
   *
   * @param string $string
   *   A string containing placeholders. The string itself is expected to be
   *   safe and correct HTML. Any unsafe content must be in $args and inserted
   *   via placeholders. It is insecure to use the @ or % placeholders within
   *   the "<"  and ">" of an HTML tag.
   * @param array $args
   *   An associative array of replacements. Each array key should be the same
   *   as a placeholder in $string. The corresponding value should be a string
   *   or an object that implements \Drupal\Component\Render\MarkupInterface.
   *   The args[] value replaces the placeholder in $string. Sanitization and
   *   formatting will be done before replacement. The type of sanitization
   *   and formatting depends on the first character of the key:
   *   - @variable: Use as the default choice for anything displayed on the
   *     site. Do not use within the "<" and ">" of an HTML tag, such as in
   *     HTML attribute values. Doing so is a security risk.
   *   - %variable: Use when @variable would be appropriate, but you want the
   *     placeholder value to be wrapped in an <em> tag with a placeholder
   *     class. As with @variable, do not use within the "<" and ">" of an HTML
   *     tag, such as in HTML attribute values. Doing so is a security risk.
   *   - :variable: Use when the return value is to be used as a URL value of an
   *     HTML attribute. Only the "href" attribute is supported. The return
   *     value is escaped with
   *     \Drupal\Component\Utility\Html::escape() and filtered for dangerous
   *     protocols using UrlHelper::stripDangerousProtocols(). Use this when
   *     using the "href" attribute, ensuring the value is always wrapped in
   *     quotes.
   *
   * @return string
   *   A formatted HTML string with the placeholders replaced.
   *
   * @ingroup sanitization
   *
   * @see \Drupal\Core\StringTranslation\TranslatableMarkup
   * @see \Drupal\Core\StringTranslation\PluralTranslatableMarkup
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
          // \Drupal\Component\Render\MarkupInterface, for example strings will
          // be escaped.
          // Strings that are safe within HTML fragments, but not within other
          // contexts, may still be an instance of
          // \Drupal\Component\Render\MarkupInterface, so this placeholder type
          // must not be used within HTML attributes, JavaScript, or CSS.
          $args[$key] = static::placeholderEscape($value);
          break;

        case ':':
          // Strip URL protocols that can be XSS vectors.
          $value = UrlHelper::stripDangerousProtocols($value);
          // Escape unconditionally, without checking whether the value is an
          // instance of \Drupal\Component\Render\MarkupInterface. This forces
          // characters that are unsafe for use in an "href" HTML attribute to
          // be encoded. If a caller wants to pass a value that is extracted
          // from HTML and therefore is already HTML encoded, it must invoke
          // \Drupal\Component\Render\OutputStrategyInterface::renderFromHtml()
          // on it prior to passing it in as a placeholder value of this type.
          // @todo Add some advice and stronger warnings.
          //   https://www.drupal.org/node/2569041.
          $args[$key] = Html::escape($value);
          break;

        case '%':
          // Similarly to @, escape non-safe values. Also, add wrapping markup
          // in order to render as a placeholder. Not for use within attributes,
          // per the warning above about
          // \Drupal\Component\Render\MarkupInterface and also due to the
          // wrapping markup.
          $args[$key] = '<em class="placeholder">' . static::placeholderEscape($value) . '</em>';
          break;

        default:
          if (!ctype_alnum($key[0])) {
            // Warn for random placeholders that won't be replaced.
            trigger_error(sprintf('Placeholders must begin with one of the following "@", ":" or "%%", invalid placeholder (%s) with string: "%s"', $key, $string), E_USER_WARNING);
          }
          // No replacement possible therefore we can discard the argument.
          unset($args[$key]);
          break;
      }
    }

    return strtr($string, $args);
  }

  /**
   * Escapes a placeholder replacement value if needed.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $value
   *   A placeholder replacement value.
   *
   * @return string
   *   The properly escaped replacement value.
   */
  protected static function placeholderEscape($value) {
    return $value instanceof MarkupInterface ? (string) $value : Html::escape($value);
  }

}
