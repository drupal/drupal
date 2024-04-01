<?php

namespace Drupal\Component\Render;

/**
 * Implements MarkupInterface and Countable for rendered objects.
 *
 * @see \Drupal\Component\Render\MarkupInterface
 */
trait MarkupTrait {

  /**
   * The safe string.
   *
   * @var string
   */
  protected $string;

  /**
   * Creates a Markup object if necessary.
   *
   * If $string is equal to a blank string then it is not necessary to create a
   * Markup object. If $string is an object that implements MarkupInterface it
   * is returned unchanged.
   *
   * @param mixed $string
   *   The string to mark as safe. This value will be cast to a string.
   *
   * @return string|\Drupal\Component\Render\MarkupInterface
   *   A safe string.
   */
  public static function create($string) {
    if ($string instanceof MarkupInterface) {
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
   * Returns the string version of the Markup object.
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

}
