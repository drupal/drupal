<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\SafeStringTrait.
 */

namespace Drupal\Component\Utility;

/**
 * Implements SafeStringInterface and Countable for rendered objects.
 *
 * @see \Drupal\Component\Utility\SafeStringInterface
 */
trait SafeStringTrait {

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
