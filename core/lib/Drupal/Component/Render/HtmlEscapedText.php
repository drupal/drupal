<?php

namespace Drupal\Component\Render;

use Drupal\Component\Utility\Html;

/**
 * Escapes HTML syntax characters to HTML entities for display in markup.
 *
 * This class can be used to provide theme engine-like late escaping
 * functionality.
 *
 * @ingroup sanitization
 */
class HtmlEscapedText implements MarkupInterface, \Countable {

  /**
   * The string to escape.
   *
   * @var string
   */
  protected $string;

  /**
   * Constructs an HtmlEscapedText object.
   *
   * @param $string
   *   The string to escape. This value will be cast to a string.
   */
  public function __construct($string) {
    $this->string = (string) $string;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return Html::escape($this->string);
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function count() {
    return mb_strlen($this->string);
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    return $this->__toString();
  }

}
