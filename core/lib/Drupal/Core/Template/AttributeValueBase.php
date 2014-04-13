<?php

/**
 * @file
 * Contains \Drupal\Core\Template\AttributeValueBase.
 */

namespace Drupal\Core\Template;

use Drupal\Component\Utility\String;

/**
 * Defines the base class for an attribute type.
 *
 * @see \Drupal\Core\Template\Attribute
 */
abstract class AttributeValueBase {

  /**
   * The value itself.
   *
   * @var mixed
   */
  protected $value;

  /**
   * The name of the value.
   *
   * @var mixed
   */
  protected $name;

  /**
   * Constructs a \Drupal\Core\Template\AttributeValueBase object.
   */
  public function __construct($name, $value) {
    $this->name = $name;
    $this->value = $value;
  }

  /**
   * Returns a string representation of the attribute.
   *
   * While __toString only returns the value in a string form, render()
   * contains the name of the attribute as well.
   *
   * @return string
   *   The string representation of the attribute.
   */
  public function render() {
    if (isset($this->value)) {
      return String::checkPlain($this->name) . '="' . $this . '"';
    }
  }

  /**
   * Implements the magic __toString() method.
   */
  abstract function __toString();

}
