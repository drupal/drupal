<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'image_button' element.
 */
class ImageButtonBuilder extends Submit {

  protected $renderable = ['#type' => 'image_button'];

  /**
   * Set the return_value property on the image_button.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setReturnValue($value) {
    $this->set('return_value', $value);
    return $this;
  }

  /**
   * Set the src property on the image_button.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setSrc($value) {
    $this->set('src', $value);
    return $this;
  }

}
