<?php

namespace Drupal\Core\Template;

use Drupal\Component\Utility\Html;

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
   * Constructs a \Drupal\Core\Template\AttributeValueBase object.
   *
   * @param mixed $name
   *   The name of the value.
   * @param mixed $value
   *   The value itself.
   */
  public function __construct(
    protected $name,
    protected $value,
  ) {
  }

  /**
   * Returns a string representation of the attribute.
   *
   * While __toString only returns the value in a string form, render()
   * contains the name of the attribute as well.
   *
   * @return string|null
   *   The string representation of the attribute.
   */
  public function render() {
    $value = (string) $this;
    if (isset($this->value) && static::RENDER_EMPTY_ATTRIBUTE || !empty($value)) {
      return Html::escape($this->name) . '="' . $value . '"';
    }
    return NULL;
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
  abstract public function __toString();

}
