<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'container' element.
 */
class ContainerBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'container'];

  /**
   * Set the attributes property on the container.
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
   * Set the children property on the container.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setChildren($value) {
    $this->set('children', $value);
    return $this;
  }

  /**
   * Set the id property on the container.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setId($value) {
    $this->set('id', $value);
    return $this;
  }

}
