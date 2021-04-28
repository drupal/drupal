<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'menu' element.
 */
class MenuBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'menu'];

  /**
   * Set the items property on the menu.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setItems($value) {
    $this->set('items', $value);
    return $this;
  }

  /**
   * Set the attributes property on the menu.
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
