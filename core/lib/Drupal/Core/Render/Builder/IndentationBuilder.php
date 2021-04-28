<?php

namespace Drupal\Core\Render\Builder;

/**
 * Builder class for the 'indentation' element.
 */
class IndentationBuilder extends BuilderBase {

  protected $renderable = ['#theme' => 'indentation'];

  /**
   * Set the size property on the indentation.
   *
   * @param mixed $value
   *   The value to set.
   *
   * @return $this
   */
  public function setSize($value) {
    $this->set('size', $value);
    return $this;
  }

}
