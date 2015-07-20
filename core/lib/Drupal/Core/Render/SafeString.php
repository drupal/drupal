<?php

/**
 * @file
 * Contains \Drupal\Core\Render\SafeString.
 */

namespace Drupal\Core\Render;

use Drupal\Component\Utility\SafeStringInterface;
use Drupal\Component\Utility\Unicode;

/**
 * Defines an object that passes safe strings through the render system.
 *
 * This object should only be constructed with a known safe string. If there is
 * any risk that the string contains user-entered data that has not been
 * filtered first, it must not be used.
 *
 * @internal
 *   This object is marked as internal because it should only be used during
 *   rendering. Currently, there is no use case for this object by contrib or
 *   custom code.
 *
 * @see \Drupal\Core\Template\TwigExtension::escapeFilter
 * @see \Twig_Markup
 * @see \Drupal\Component\Utility\SafeMarkup
 */
class SafeString implements SafeStringInterface, \Countable {

  /**
   * The safe string.
   *
   * @var string
   */
  protected $string;

  /**
   * Creates a SafeString object if necessary.
   *
   * If $string is equal to a blank string then it is not necessary to create a
   * SafeString object. If $string is an object that implements
   * SafeStringInterface it is returned unchanged.
   *
   * @param mixed $string
   *   The string to mark as safe. This value will be cast to a string.
   *
   * @return string|\Drupal\Component\Utility\SafeStringInterface
   *   A safe string.
   */
  public static function create($string) {
    if ($string instanceof SafeStringInterface) {
      return $string;
    }
    $string = (string) $string;
    if ($string === '') {
      return '';
    }
    $safe_string = new static();
    $safe_string->string = $string;
    return $safe_string;
  }

  /**
   * Returns the string version of the SafeString object.
   *
   * @return string
   *   The safe string content.
   */
  public function __toString() {
    return $this->string;
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

}
