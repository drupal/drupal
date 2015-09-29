<?php

/**
 * @file
 * Contains Drupal\Component\Utility\FormattableString.
 */

namespace Drupal\Component\Utility;

/**
 * Formats a string for HTML display by replacing variable placeholders.
 *
 * When cast to a string, this object replaces variable placeholders in the
 * string with the arguments passed in during construction and escapes the
 * values so they can be safely displayed as HTML. See the documentation of
 * \Drupal\Component\Utility\PlaceholderTrait::placeholderFormat() for
 * details on the supported placeholders and how to use them securely. Incorrect
 * use of this class can result in security vulnerabilities.
 *
 * In most cases, you should use TranslatableString or PluralTranslatableString
 * rather than this object, since they will translate the text (on
 * non-English-only sites) in addition to formatting it. Variables concatenated
 * without the insertion of language-specific words or punctuation are some
 * examples where translation is not applicable and using this class directly
 * directly is appropriate.
 *
 * This class is designed for formatting messages that are mostly text, not as
 * an HTML template language. As such:
 * - The passed in string should contain no (or minimal) HTML.
 * - Variable placeholders should not be used within the "<" and ">" of an
 *   HTML tag, such as in HTML attribute values. This would be a security
 *   risk.
 * To build non-minimal HTML, use an HTML template language such as Twig,
 * rather than this class.
 *
 * @ingroup sanitization
 *
 * @see \Drupal\Core\StringTranslation\TranslatableString
 * @see \Drupal\Core\StringTranslation\PluralTranslatableString
 * @see \Drupal\Component\Utility\PlaceholderTrait::placeholderFormat()
 */
class FormattableString implements SafeStringInterface {

  use PlaceholderTrait;

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
   * @param array $args
   *   An array with placeholder replacements, keyed by placeholder. See
   *   \Drupal\Component\Utility\PlaceholderTrait::placeholderFormat() for
   *   additional information about placeholders.
   *
   * @see \Drupal\Component\Utility\PlaceholderTrait::placeholderFormat()
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
  public function count() {
    return Unicode::strlen($this->string);
  }

  /**
   * Returns a representation of the object for use in JSON serialization.
   *
   * @return string
   *   The safe string content.
   */
  public function jsonSerialize() {
    return $this->__toString();
  }

}
