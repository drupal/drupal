<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'image_formatter' element.
 */
class ImageFormatterBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'image_formatter'];

  /**
   * Set the item property on the image_formatter.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setItem($value) {
    $this->set('item', $value);
    return $this;
  }

  /**
   * Set the item_attributes property on the image_formatter.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setItemAttributes($value) {
    $this->set('item_attributes', $value);
    return $this;
  }

  /**
   * Set the url property on the image_formatter.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setUrl($value) {
    $this->set('url', $value);
    return $this;
  }

  /**
   * Set the image_style property on the image_formatter.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setImageStyle($value) {
    $this->set('image_style', $value);
    return $this;
  }

}
