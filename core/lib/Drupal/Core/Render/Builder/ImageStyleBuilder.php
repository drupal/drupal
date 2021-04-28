<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'image_style' element.
 */
class ImageStyleBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'image_style'];

  /**
   * Set the style_name property on the image_style.
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

  /**
   * Set the uri property on the image_style.
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
   * Set the width property on the image_style.
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
   * Set the height property on the image_style.
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
   * Set the alt property on the image_style.
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
   * Set the title property on the image_style.
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
   * Set the attributes property on the image_style.
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

}
