<?php

namespace Drupal\Tests\Component\Utility;

/**
 * Used by SafeMarkupTest to test that a class with a __toString() method works.
 */
class TextWrapper {

  /**
   * The text value.
   *
   * @var string
   */
  protected $text = '';

  /**
   * Constructs a \Drupal\Tests\Component\Utility\TextWrapper
   *
   * @param string $text
   */
  public function __construct($text) {
    $this->text = $text;
  }

  /**
   * Magic method
   *
   * @return string
   */
  public function __toString() {
    return $this->text;
  }

}
