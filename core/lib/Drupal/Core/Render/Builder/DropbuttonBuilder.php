<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'dropbutton' element.
 */
class DropbuttonBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'dropbutton'];

  /**
   * Set the links property on the dropbutton.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setLinks($value) {
    $this->set('links', $value);
    return $this;
  }

  /**
   * Set the attributes property on the dropbutton.
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
   * Set the heading property on the dropbutton.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setHeading($value) {
    $this->set('heading', $value);
    return $this;
  }

  /**
   * Set the set_active_class property on the dropbutton.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setSetActiveClass($value) {
    $this->set('set_active_class', $value);
    return $this;
  }

}
