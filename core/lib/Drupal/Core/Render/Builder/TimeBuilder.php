<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'time' element.
 */
class TimeBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'time'];

  /**
   * Set the timestamp property on the time.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setTimestamp($value) {
    $this->set('timestamp', $value);
    return $this;
  }

  /**
   * Set the text property on the time.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setText($value) {
    $this->set('text', $value);
    return $this;
  }

  /**
   * Set the attributes property on the time.
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
