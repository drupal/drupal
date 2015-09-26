<?php

/**
 * @file
 * Contains Drupal\Component\Utility\FormattableString.
 */

namespace Drupal\Component\Utility;

/**
 * Formats a string for HTML display by replacing variable placeholders.
 *
 * When cast to a string it replaces variable placeholders in the string with
 * the arguments passed in during construction and escapes the values so they
 * can be safely displayed as HTML. It should be used on any unknown text that
 * is intended to be printed to an HTML page (especially text that may have come
 * from untrusted users, since in that case it prevents cross-site scripting and
 * other security problems).
 *
 * This class is not intended for passing arbitrary user input into any HTML
 * attribute value, as only URL attributes such as "src" and "href" are
 * supported (using ":variable"). Never use this method on unsafe HTML
 * attributes such as "on*" and "style" and take care when using this with
 * unsupported attributes such as "title" or "alt" as this can lead to
 * unexpected output.
 *
 * In most cases, you should use TranslatableString or PluralTranslatableString
 * rather than this object, since they will translate the text (on
 * non-English-only sites) in addition to formatting it.
 *
 * @ingroup sanitization
 *
 * @see \Drupal\Core\StringTranslation\TranslatableString
 * @see \Drupal\Core\StringTranslation\PluralTranslatableString
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
