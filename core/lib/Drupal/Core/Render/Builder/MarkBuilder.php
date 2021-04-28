<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'mark' element.
 */
class MarkBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'mark'];

  /**
   * Set the status property on the mark.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setStatus($value) {
    $this->set('status', $value);
    return $this;
  }

}
