<?php

/**
 * @file
 * Contains \Drupal\Core\Page\MetaElement.
 */

namespace Drupal\Core\Page;

/**
 * Defines a metatag HTML head element which is defined by the name and content.
 */
class MetaElement extends HeadElement {

  /**
   * {@inheritdoc}
   */
  protected $element = 'meta';

  /**
   * Constructs a new MetaElement instance.
   *
   * @param string $content
   *   (optional) The value of the attribute, defaults to an empty string.
   * @param array $attributes
   *   (optional) Additional attributes for this element, defaults to an empty
   *   array.
   */
  public function __construct($content = '', array $attributes = array()) {
    $this->attributes = $attributes + array(
      'content' => $content,
    );
  }

  /**
   * Sets the name attribute.
   *
   * @param string $name
   *   The name attribute value to set.
   *
   * @return $this
   */
  public function setName($name) {
    $this->attributes['name'] = $name;
    return $this;
  }

  /**
   * Sets the content attribute.
   *
   * @param string $content
   *   The content attribute value to set.
   *
   * @return $this
   */
  public function setContent($content) {
    $this->attributes['content'] = $content;
    return $this;
  }

  /**
   * Gets the name.
   *
   * @return string
   *   The name of the metatag element.
   */
  public function getName() {
    return $this->attributes['name'];
  }

  /**
   * Gets the content.
   *
   * @return string
   *   The content of the metatag.
   */
  public function getContent() {
    return $this->attributes['content'];
  }

}
