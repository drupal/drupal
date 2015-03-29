<?php

/**
 * @file
 * Contains \Drupal\Core\Template\AttributeValueBase.
 */

namespace Drupal\Core\Template;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Defines the base class for an attribute type.
 *
 * @see \Drupal\Core\Template\Attribute
 */
abstract class AttributeValueBase {

  /**
   * Renders '$name=""' if $value is an empty string.
   *
   * @see \Drupal\Core\Template\AttributeValueBase::render()
   */
  const RENDER_EMPTY_ATTRIBUTE = TRUE;

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
    $value = (string) $this;
    if (isset($this->value) && static::RENDER_EMPTY_ATTRIBUTE || !empty($value)) {
      return SafeMarkup::checkPlain($this->name) . '="' . $value . '"';
    }
  }

  /**
   * Returns the raw value.
   */
  public function value() {
    return $this->value;
  }

  /**
   * Implements the magic __toString() method.
   */
  abstract function __toString();

}
