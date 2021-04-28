<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'pager' element.
 */
class PagerBuilder extends BuilderBase {

  protected $renderable = ['#type' => 'pager'];

  /**
   * Set the items property on the pager.
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
   * Set the current property on the pager.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setCurrent($value) {
    $this->set('current', $value);
    return $this;
  }

  /**
   * Set the ellipses property on the pager.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setEllipses($value) {
    $this->set('ellipses', $value);
    return $this;
  }

}
