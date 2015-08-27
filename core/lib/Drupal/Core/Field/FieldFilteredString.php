<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldFilteredString.
 */

namespace Drupal\Core\Field;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeStringInterface;
use Drupal\Component\Utility\SafeStringTrait;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;

/**
 * Defines an object that passes safe strings through the Field system.
 *
 * This object filters the string using a very restrictive tag list when it is
 * created.
 *
 * @internal
 *   This object is marked as internal because it should only be used by the
 *   Field module and field-related plugins.
 *
 * @see \Drupal\Core\Render\SafeString
 */
final class FieldFilteredString implements SafeStringInterface, \Countable {
  use SafeStringTrait;

  /**
   * Overrides \Drupal\Component\Utility\SafeStringTrait::create().
   *
   * @return string|\Drupal\Component\Utility\SafeStringInterface
   *   A safe string filtered with the allowed tag list and normalized.
   *
   * @see \Drupal\Core\Field\FieldFilteredString::allowedTags()
   * @see \Drupal\Component\Utility\Xss::filter()
   * @see \Drupal\Component\Utility\Html::normalize()
   */
  public static function create($string) {
    $string = (string) $string;
    if ($string === '') {
      return '';
    }
    $safe_string = new static();
    // All known XSS vectors are filtered out by
    // \Drupal\Component\Utility\Xss::filter(), all tags in the markup are
    // allowed intentionally by the trait, and no danger is added in by
    // \Drupal\Component\Utility\HTML::normalize(). Since the normalized value
    // is essentially the same markup, designate this string as safe as well.
    // This method is an internal part of field sanitization, so the resultant,
    // sanitized string should be printable as is.
    $safe_string->string = Html::normalize(Xss::filter($string, static::allowedTags()));
    return $safe_string;
  }

  /**
   * Returns the allowed tag list.
   *
   * @return string[]
   *   A list of allowed tags.
   */
  public static function allowedTags() {
    return ['a', 'b', 'big', 'code', 'del', 'em', 'i', 'ins',  'pre', 'q', 'small', 'span', 'strong', 'sub', 'sup', 'tt', 'ol', 'ul', 'li', 'p', 'br', 'img'];
  }

  /**
   * Returns a human-readable list of allowed tags for display in help texts.
   *
   * @return string
   *   A human-readable list of allowed tags for display in help texts.
   */
  public static function displayAllowedTags() {
    return '<' . implode('> <', static::allowedTags()) . '>';
  }

}
