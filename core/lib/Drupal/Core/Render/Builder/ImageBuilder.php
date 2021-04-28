<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'image' element.
 */
class ImageBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'image'];

  /**
   * Set the uri property on the image.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setUri($value) {
    $this->set('uri', $value);
    return $this;
  }

  /**
   * Set the width property on the image.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setWidth($value) {
    $this->set('width', $value);
    return $this;
  }

  /**
   * Set the height property on the image.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setHeight($value) {
    $this->set('height', $value);
    return $this;
  }

  /**
   * Set the alt property on the image.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setAlt($value) {
    $this->set('alt', $value);
    return $this;
  }

  /**
   * Set the title property on the image.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTitle($value) {
    $this->set('title', $value);
    return $this;
  }

  /**
   * Set the attributes property on the image.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setAttributes($value) {
    $this->set('attributes', $value);
    return $this;
  }

  /**
   * Set the sizes property on the image.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setSizes($value) {
    $this->set('sizes', $value);
    return $this;
  }

  /**
   * Set the srcset property on the image.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setSrcset($value) {
    $this->set('srcset', $value);
    return $this;
  }

  /**
   * Set the style_name property on the image.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setStyleName($value) {
    $this->set('style_name', $value);
    return $this;
  }

}
