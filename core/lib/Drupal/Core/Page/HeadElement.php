<?php

/**
 * @file
 * Contains \Drupal\Core\Page\HeadElement.
 */

namespace Drupal\Core\Page;

use Drupal\Core\Template\Attribute;

/**
 * This class represents an HTML element that appears in the HEAD tag.
 */
class HeadElement {

  /**
   * An array of attributes for this element.
   *
   * @var array
   */
  protected $attributes = array();

  /**
   * The element name.
   *
   * Sub-classes should override this value with the name of their element.
   *
   * @var string
   */
  protected $element = '';

  /**
   * If this element should be wrapped in <noscript>.
   *
   * @var bool
   */
  protected $noScript = FALSE;

  /**
   * Renders this object to an HTML element string.
   *
   * @return string
   */
  public function __toString() {
    // Render the attributes via the attribute template class.
    // @todo Should HeadElement just extend the Attribute classes?
    $attributes = new Attribute($this->attributes);
    $rendered = (string) $attributes;

    $string = "<{$this->element}{$rendered} />";
    if ($this->noScript) {
      $string = "<noscript>$string</noscript>";
    }
    return $string;
  }

  /**
   * Sets an attribute on this element.
   *
   * @param mixed $key
   *   The attribute to set.
   * @param mixed $value
   *   The value to which to set it.
   *
   * @return self
   *   The invoked object.
   */
  public function setAttribute($key, $value) {
    $this->attributes[$key] = $value;
    return $this;
  }

  /**
   * Gets all the attributes.
   *
   * @return array
   *   An array of all the attributes keyed by name of attribute.
   */
  public function &getAttributes() {
    return $this->attributes;
  }

  /**
   * Sets if this element should be wrapped in <noscript>.
   *
   * @param bool $value
   *   (optional) Whether or not this element should be wrapped in <noscript>.
   *   Defaults to TRUE.
   *
   * @return self
   *   The element..
   */
  public function setNoScript($value = TRUE) {
    $this->noScript = $value;
    return $this;
  }

}
